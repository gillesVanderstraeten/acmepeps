<?php

declare(strict_types=1);

namespace controllers;

use entities\Category;
use entities\Product;
use entities\User;
use Exception;
use PDOException;
use peps\core\Cfg;
use peps\core\DBAL;
use peps\core\Router;
use peps\image\ImageException;
use peps\image\ImageJpeg;
use peps\upload\Upload;
use peps\upload\UploadException;
use SplFileInfo;

/**
 * Classe 100% statique de contrôle des produits.
 * 
 * @see Product
 * @see Category
 * @see Upload
 * @see ImageJpeg
 * @copyright 2020-2022 Gilles VANDERSTRAETEN gillesvds@adok.info
 */
final class ProductController
{
	// Messages d'erreur.
	private const ERR_INVALID_NAME = "Nom invalide.";
	private const ERR_INVALID_REF = "Référence invalide.";
	private const ERR_DUPLICATE_REFERENCE = "Référence déjà existante.";
	private const ERR_INVALID_PRICE = "Prix invalide.";

	/**
	 * Constructeur privé.
	 */
	private function __construct()
	{
	}

	/**
	 * Affiche la liste des produits par catégorie.
	 * 
	 * GET /
	 * GET /product/list
	 */
	public static function list(): void
	{
		// Récupérer toutes les catégories.
		$categories = Category::findAllBy([], ['name' => 'ASC']);
		// Ajouter dynamiquement les propriétés idImg et mtime à chaque produit de chaque catégorie.
		foreach ($categories as $category) {
			foreach ($category->products as $product)
				self::addDynamicProperties($product);
		}
		// Rendre la vue.
		Router::render('listProducts.php', ['categories' => $categories]);
	}

	/**
	 * Affiche le détail d'un produit.
	 * 
	 * GET /product/show/{idProduit}
	 * 
	 * @param array $params Tableau associatif des paramètres.
	 */
	public static function show(array $params): void
	{
		// Récupérer idProduct.
		$idProduct = (int) $params['idProduct'];
		// Créer le produit.
		$product = new Product($idProduct);
		// Hydrater le produit et si échec, rendre la vue noProduct.
		if (!$product->hydrate())
			Router::render('noProduct.php');
		// Ajouter dynamiquement les propriétés idImg et mtime au produit.
		self::addDynamicProperties($product);
		// Rendre la vue.
		Router::render('showProduct.php', ['product' => $product]);
	}

	/**
	 * Supprime un produit et/ou ses images.
	 * 
	 * GET /product/delete/{idProduct}/{mode = all | img}
	 *
	 * @param array $params Tableau associatif des paramètres.
	 */
	public static function delete(array $params): void
	{
		// Si l'utilisateur n'est pas logué, rediriger vers le formulaire de connexion.
		if (!User::getUserSession())
			Router::redirect('/user/signin');
		// Récupérer idProduct et mode.
		$idProduct = (int) $params['idProduct'];
		$mode = $params['mode'];
		// Si mode all, créer un produit pour le supprimer.
		if ($mode === 'all') (new Product($idProduct))->remove();
		// Dans tous les cas, tenter de supprimer les images.
		@unlink("assets/img/products/product_{$idProduct}.jpg"); // @ pour éviter le warning.
		// Rediriger vers la liste.
		Router::redirect('/product/list');
	}

	/**
	 * Affiche le formulaire d'ajout d'un produit.
	 * 
	 * GET /product/create/{idCategory}
	 *
	 * @param array $params Tableau associatif des paramètres.
	 */
	public static function create(array $params): void
	{
		// Si l'utilisateur n'est pas logué, rediriger vers le formulaire de connexion.
		if (!User::getUserSession())
			Router::redirect('/user/signin');
		// Récupérer idCategory.
		$idCategory = (int) $params['idCategory'];
		// Créer un produit (attendu par la vue).
		$product = new Product();
		// Renseigner l'idCategory du produit pour caler le menu déroulant des catégories.
		$product->idCategory = $idCategory;
		// Rendre la vue.
		self::renderForm($product);
	}

	/**
	 * Affiche le formulaire de modification d'un produit.
	 * 
	 * GET /product/update/{idProduct}
	 *
	 * @param array $params Tableau associatif des paramètres.
	 */
	public static function update(array $params): void
	{
		// Si l'utilisateur n'est pas logué, rediriger vers le formulaire de connexion.
		if (!User::getUserSession())
			Router::redirect('/user/signin');
		// Récupérer idProduct.
		$idProduct = (int) $params['idProduct'];
		// Créer le produit correspondant.
		$product = new Product($idProduct);
		// Hydrater le produit et si échec, rendre la vue 'noProduct'.
		if (!$product->hydrate())
			Router::render('noProduct.php');
		// Rendre la vue.
		self::renderForm($product);
	}

	/**
	 * Persiste le produit en ajout ou modification.
	 * 
	 * POST /product/save
	 */
	public static function save(): void
	{
		// Si l'utilisateur n'est pas logué, rediriger vers le formulaire de connexion.
		if (!User::getUserSession())
			Router::redirect('/user/signin');
		// Créer le produit.
		$product = new Product();
		// Initialiser le tableau des messages d'erreurs.
		$errors = [];
		// Si aucune donnée POST (un fichier trop lourd a été uploadé), rendre la vue du formulaire de saisie avec le message d'erreur.
		if (!filter_input(INPUT_POST, 'submit')) {
			$errors[] = UploadException::FILE_SIZE_EXCEEDS_CLIENT_OR_SERVER_LIMIT;
			self::renderForm($product, $errors);
		}
		// Récupérer les donnéees POST.
		$product->idProduct = filter_input(INPUT_POST, 'idProduct', FILTER_VALIDATE_INT) ?: null;
		$product->idCategory = filter_input(INPUT_POST, 'idCategory', FILTER_VALIDATE_INT) ?: null;
		$product->name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING) ?: null;
		$product->ref = filter_input(INPUT_POST, 'ref', FILTER_SANITIZE_STRING) ?: null;
		$product->price = filter_input(INPUT_POST, 'price', FILTER_VALIDATE_FLOAT) ?: null;
		// Créer un booléen pour retenir si update ou pas.
		$update = (bool) $product->idProduct;
		// Vérifier le nom (obligatoire et max 50 caractères).
		if (!$product->name || mb_strlen($product->name) > 50)
			$errors[] = self::ERR_INVALID_NAME;
		// Vérifier la référence (obligatoire et max 10 caractères).
		if (!$product->ref || mb_strlen($product->ref) > 10)
			$errors[] = self::ERR_INVALID_REF;
		// Vérifier le prix (obligatoire et > 0 et < 10000).
		if (!$product->price || $product->price <= 0 || $product->price >= 10000)
			$errors[] = self::ERR_INVALID_PRICE;
		// Si erreurs, rendre la vue du formulaire de saisie avec les messages d'erreur.
		if ($errors)
			self::renderForm($product, $errors);
		// Commencer une transaction.
		DBAL::get()->start();
		try {
			// D'abord, persister le produit (pour obtenir sa PK auto-incrémentée).
			$product->persist();
			// Vérifier l'upload.
			$upload = new Upload('photo', Cfg::get('imgMaxFileSize'), Cfg::get('imgAllowedMimeTypes'));
			// Si l'upload est complet, sauvegarder le fichier.
			if ($upload->complete) {
				$image = new ImageJpeg($upload->tmpFilePath);
				$image->copyResize(Cfg::get('imgWidth'), Cfg::get('imgHeight'), "assets/img/products/product_{$product->idProduct}.jpg");
			}
			// Si pas de fichier, valider la transaction et rediriger vers la liste.
			else {
				DBAL::get()->commit();
				Router::redirect('/product/list');
			}
		} catch (PDOException $e) {
			// La persistance a échouée, le doublon de référence est la seule cause d'erreur honnête possible.
			self::renderForm($product, [self::ERR_DUPLICATE_REFERENCE]);
		} catch (UploadException | ImageException $e) {
			// L'upload ou le traitement de l'image a échoué, faire un roolback et rendre la vue du formulaire de saisie avec le message d'erreur.
			DBAL::get()->rollback();
			// Si create (et pas update), supprimer la PK auto-incrémentée.
			if (!$update)
				$product->idProduct = null;
			self::renderForm($product, [$e->getMessage()]);
		}
		// Tout est OK, valider la transaction et rediriger vers la liste.
		DBAL::get()->commit();
		Router::redirect('/product/list');
	}

	/**
	 * Ajoute dynamiquement les propriétés idImg et mtime au produit.
	 *
	 * @param Product $product Produit.
	 */
	private static function addDynamicProperties(Product $product): void
	{
		try {
			$product->idImg = $product->idProduct;
			$product->mtime = (new SplFileInfo("assets/img/products/product_{$product->idProduct}.jpg"))->getMTime();
		} catch (Exception $e) {
			$product->idImg = $product->mtime = 0;
		}
	}

	/**
	 * Définit les variables attendues puis rend la vue du formulaire de saisie.
	 *
	 * @param Product $product Produit.
	 * @param string[] $errors Tableau des messages d'erreur.
	 */
	private static function renderForm(Product $product, array $errors = []): void
	{
		// Récupérer toutes les catégories pour peupler le menu déroulant.
		$categories = Category::findAllBy([], ['name' => 'ASC']);
		// Si PK renseignée, ajouter dynamiquement les propriétés 'idImg' et 'mtime' au produit.
		if ($product->idProduct)
			self::addDynamicProperties($product);
		// Rendre la vue.
		Router::render('editProduct.php', ['product' => $product, 'categories' => $categories, 'errors' => $errors]);
	}
}

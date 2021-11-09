<?php

declare(strict_types=1);

namespace entities;

use peps\core\ORMDB;

/**
 * Entité Product.
 * Toutes les propriétés à null par défaut pour les formulaires de saisie.
 * 
 * @see ORMDB
 * @copyright 2020-2022 Gilles VANDERSTRAETEN gillesvds@adok.info
 */
class Product extends ORMDB
{
	/**
	 * PK.
	 */
	public ?int $idProduct = null;

	/**
	 * FK de la catégorie.
	 */
	public ?int $idCategory = null;

	/**
	 * Nom.
	 */
	public ?string $name = null;

	/**
	 * Référence.
	 */
	public ?string $ref = null;

	/**
	 * Prix.
	 */
	public ?float $price = null;

	/**
	 * Catégorie de ce produit.
	 */
	protected ?Category $category = null;

	/**
	 * Constructeur.
	 */
	public function __construct(int $idProduct = null)
	{
		$this->idProduct = $idProduct;
	}

	/**
	 * Retourne la catégorie de ce produit.
	 * Lazy loading.
	 * 
	 * @return Category La catégorie.
	 */
	protected function getCategory(): Category
	{
		// Si la catégorie n'est pas renseignée, requêter la DB.
		if (empty($this->category))
			$this->category = Category::findOneBy(['idCategory' => $this->idCategory]);
		return $this->category;
	}
}

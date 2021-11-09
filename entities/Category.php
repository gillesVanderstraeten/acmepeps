<?php

declare(strict_types=1);

namespace entities;

use peps\core\ORMDB;

/**
 * Entité Category.
 * Toutes les propriétés à null par défaut pour les formulaires de saisie.
 * 
 * @see ORMDB
 * @copyright 2020-2022 Gilles VANDERSTRAETEN gillesvds@adok.info
 */
class Category extends ORMDB
{
	/**
	 * PK.
	 */
	public ?int $idCategory = null;

	/**
	 * Nom.
	 */
	public ?string $name = null;

	/**
	 * Collection des produits de cette catégorie.
	 * 
	 * @var Product[] | null
	 */
	protected ?array $products = null;

	/**
	 * Constructeur.
	 */
	public function __construct(int $idCategory = null)
	{
		$this->idCategory = $idCategory;
	}

	/**
	 * Retourne un tableau des produits (triés par nom) de cette catégorie.
	 * Lazy loading.
	 *
	 * @return Product[] Tableau des produits.
	 */
	protected function getProducts(): array
	{
		// Si le tableau n'est pas renseigné, requêter la DB.
		if (empty($this->products))
			$this->products = Product::findAllBy(['idCategory' => $this->idCategory], ['name' => 'ASC']);
		return $this->products;
	}
}

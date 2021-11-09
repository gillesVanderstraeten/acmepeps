<?php

declare(strict_types=1);

namespace peps\core;

use ReflectionClass;
use ReflectionProperty;

/**
 * Implémentation de la persistance ORM en DB via DBAL.
 * Les classes entités DEVRAIENT étendre cette classe.
 * *********************************************************
 * Règles à respecter pour profiter de cette implémentation.
 * Sinon, redéfinir ces méthodes dans les classes entités.
 * *********************************************************
 * -1- Tables nommées selon cet exemple: classe 'TrucChose', table 'trucChose'.
 * -2- PK auto-incrémentée nommée selon cet exemple: table 'trucChose', PK 'idTrucChose'.
 * -3- Chaque colonne correspond à une propriété PUBLIC du même nom. Les autres propriétés NE sont PAS PUBLIC.
 * 
 * @see ORM
 * @copyright 2020-2022 Gilles VANDERSTRAETEN gillesvds@adok.info
 */
class ORMDB extends ORM
{
	/**
	 * {@inheritDoc}
	 */
	public function hydrate(): bool
	{
		// Récupérer le nom court (pas pleinement qualifié) de la classe de l'entité $this pour en déduire le nom de la table.
		$className = (new ReflectionClass($this))->getShortName();
		$tableName = lcfirst($className);
		// Construire le nom de la PK à partir du nom de la classe.
		$pkName = "id{$className}";
		$q = "SELECT * FROM {$tableName} WHERE {$pkName} = :__ID__";
		$params = [':__ID__' => $this->$pkName];
		// Exécuter la requête et hydrater $this.
		return DBAL::get()->xeq($q, $params)->into($this);
	}

	/**
	 * {@inheritDoc}
	 */
	public function persist(): bool
	{
		// Récupérer le nom court (pas pleinement qualifié) de la classe de l'entité $this pour en déduire le nom de la table.
		$rc = new ReflectionClass($this);
		$className = $rc->getShortName();
		$tableName = lcfirst($className);
		// Construire le nom de la PK à partir du nom de la classe.
		$pkName = "id{$className}";
		// Récupérer le tableau des propriétés publiques de la classe.
		$properties = $rc->getProperties(ReflectionProperty::IS_PUBLIC);
		// Initialiser les éléments de requêtes et le tableau des paramètres.
		$strInsertColums = $strInsertValues = $strUpdate = '';
		$params = [];
		// Pour chaque propriété, construire les éléments des requêtes SQL et compléter les paramètres.
		foreach ($properties as $property) {
			$propertyName = $property->getName();
			$strInsertColums .= "{$propertyName},";
			$strInsertValues .= ":{$propertyName},";
			$strUpdate .= "{$propertyName} = :{$propertyName},";
			$params[":{$propertyName}"] = $this->$propertyName;
		}
		// Supprimer la dernière virgule de chaque élément de requête.
		$strInsertColums = rtrim($strInsertColums, ',');
		$strInsertValues = rtrim($strInsertValues, ',');
		$strUpdate = rtrim($strUpdate, ',');
		// Créer les requêtes et les paramètres SQL.
		$strInsert = "INSERT INTO {$tableName} ({$strInsertColums}) VALUES({$strInsertValues})";
		$strUpdate = "UPDATE {$tableName} SET {$strUpdate} WHERE {$pkName} = :__ID__";
		$paramsInsert = $paramsUpdate = $params;
		$paramsUpdate[':__ID__'] = $this->$pkName;
		// Exécuter la requête INSERT ou UPDATE et, si INSERT, récupérer la PK auto-incrémentée.
		$dbal = DBAL::get();
		$this->$pkName ? $dbal->xeq($strUpdate, $paramsUpdate) : $this->$pkName = $dbal->xeq($strInsert, $paramsInsert)->pk();
		// Retourner true systématiquement.
		return true;
	}

	/**
	 * {@inheritDoc}
	 */
	public function remove(): bool
	{
		// Récupérer le nom court (pas pleinement qualifié) de la classe de l'entité $this pour en déduire le nom de la table.
		$rc = new ReflectionClass($this);
		$className = $rc->getShortName();
		$tableName = lcfirst($className);
		// Construire le nom de la PK à partir du nom de la classe.
		$pkName = "id{$className}";
		// Si PK non renseignée, retourner false.
		if (!$this->$pkName)
			return false;
		// Exécuter la requête et retourner la conclusion.
		$q = "DELETE FROM {$tableName} WHERE {$pkName} = :__ID__";
		$params = [':__ID__' => $this->$pkName];
		return (bool) DBAL::get()->xeq($q, $params)->nb();
	}

	/**
	 * {@inheritDoc}
	 */
	public static function findAllBy(array $filters = [], array $sortKeys = [], string $limit = ''): array
	{
		// Récupérer le nom court (pas pleinement qualifié) de la classe de l'entité $this pour en déduire le nom de la table.
		$className = (new ReflectionClass(static::class))->getShortName();
		$tableName = lcfirst($className);
		// Initialiser les requêtes SQL et les paramètres SQL.
		$q = "SELECT * FROM {$tableName}";
		$params = [];
		if ($filters) {
			// Si filtres, construire la clause WHERE.
			$q .= " WHERE";
			foreach ($filters as $col => $val) {
				$q .= " {$col} = :{$col} AND";
				$params[":{$col}"] = $val;
			}
			// Supprimer le dernier ' AND'.
			$q = rtrim($q, ' AND');
		}
		if ($sortKeys) {
			// Si clés de tri, construire la clause ORDER BY.
			$q .= " ORDER BY";
			foreach ($sortKeys as $col => $sortOrder)
				$q .= " {$col} {$sortOrder},";
			// Supprimer la dernière virgule.
			$q = rtrim($q, ',');
		}
		// Si limite, ajouter la clause LIMIT.
		if ($limit)
			$q .= " LIMIT {$limit}";
		// Exécuter la requête et retourner le tableau.
		return DBAL::get()->xeq($q, $params)->findAll(static::class);
	}

	/**
	 * {@inheritDoc}
	 */
	public static function findOneBy(array $filters = []): ?ORM
	{
		return self::findAllBy($filters, [], '1')[0] ?? null;
	}
}

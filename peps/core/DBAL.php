<?php

declare(strict_types=1);

namespace peps\core;

use PDO;
use PDOStatement;

/**
 * DBAL via PDO.
 * Design Pattern Singleton.
 * 
 * @copyright 2020-2022 Gilles VANDERSTRAETEN gillesvds@adok.info
 */
final class DBAL
{
	/**
	 * Options de connexion communes à toutes les DB :
	 *   - Gestion des erreurs basée sur des exceptions.
	 *   - Typage des colonnes respecté.
	 *   - Requêtes réellement préparées plutôt que simplement simulées.
	 */
	private const OPTIONS = [
		PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
		PDO::ATTR_STRINGIFY_FETCHES => false,
		PDO::ATTR_EMULATE_PREPARES => false
	];

	/**
	 * Instance Singleton.
	 */
	private static ?self $instance = null;

	/**
	 * Instance de PDO.
	 */
	private ?PDO $db = null;

	/**
	 * Instance de PDOStatement.
	 */
	private ?PDOStatement $stmt = null;

	/**
	 * Nombre d'enregistrements retrouvés (SELECT) ou affectés par la dernière requête.
	 */
	private ?int $nb = null;

	/**
	 * Constructeur privé.
	 */
	private function __construct()
	{
	}

	/**
	 * Crée l'instance Singleton et l'instance PDO encapsulée.
	 *
	 * @param string $driver Driver DB.
	 * @param string $host Hôte DB.
	 * @param integer $port Port de l'hôte DB.
	 * @param string $dbName Nom de la base de données.
	 * @param string $log Identifiant de l'utilisateur DB.
	 * @param string $pwd Mot de passe de l'utilisateur DB.
	 * @param string $charset Jeu de caractères.
	 */
	public static function init(
		string $driver,
		string $host,
		int $port,
		string $dbName,
		string $log,
		string $pwd,
		string $charset
	): void {
		// Si déjà initialisée, ne rien faire.
		if (self::$instance)
			return;
		// Créer la chaîne DSN.
		$dsn = "{$driver}:host={$host};port={$port};dbname={$dbName};charset={$charset}";
		// Créer l'instance Singleton.
		self::$instance = new self();
		// Créer l'instance PDO.
		self::$instance->db = new PDO($dsn, $log, $pwd, self::OPTIONS);
	}

	/**
	 * Retourne l'instance Singleton.
	 * La méthode init() DEVRAIT avoir été appelée au préalable.
	 * 
	 * @return self|null Instance Singleton ou null si init() pas encore appelée.
	 */
	public static function get(): ?self
	{
		return self::$instance;
	}

	/**
	 * Exécute une requête SQL.
	 * Retourne $this pour chaînage.
	 *
	 * @param string $q Requête SQL.
	 * @param array|null $params Tableau associatifs des paramètres (optionnel).
	 * @return static $this pour chaînage.
	 */
	public function xeq(string $q, ?array $params = null): static
	{
		if ($params) {
			// Si paramètres présents, préparer et exécuter la requête.
			$this->stmt = $this->db->prepare($q);
			$this->stmt->execute($params);
			// Récupérer le nombre d'enregistrements retrouvés ou affectés.
			$this->nb = $this->stmt->rowCount();
		} elseif (mb_stripos(ltrim($q), 'SELECT') === 0) {
			// Si requête SELECT, l'executer avec query().
			$this->stmt = $this->db->query($q);
			// Récupérer le nombre d'enregistrements retrouvés.
			$this->nb = $this->stmt->rowCount();
		} else {
			// Si requête NON SELECT, l'executer et récupérer le nombre d'enregistrements affectés.
			$this->nb = $this->db->exec($q);
		}
		return $this;
	}

	/**
	 * Retourne le nombre d'enregistrements retrouvés (SELECT) ou affectés par la dernière requête exécutée.
	 *
	 * @return integer Le nombre d'enregistrements.
	 */
	public function nb(): int
	{
		return $this->nb;
	}

	/**
	 * Retourne un tableau d'instances d'une classe donnée en exploitant le dernier jeu d'enregistrements.
	 * Une requête SELECT DEVRAIT avoir été exécutée préalablement.
	 *
	 * @param string $className La classe donnée.
	 * @return array Tableau d'instances de la classe donnée.
	 */
	public function findAll(string $className = 'stdClass'): array
	{
		// Si pas de recordset, retourner un tableau vide.
		if (!$this->stmt)
			return [];
		// Sinon, exploiter le recordset et retourner un tableau d'instances.
		$this->stmt->setFetchMode(PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE, $className);
		return $this->stmt->fetchAll();
	}

	/**
	 * Retourne une instance d'une classe donnée en exploitant le premier des enregistrements du dernier jeu.
	 * Une requête SELECT (typiquement retrouvant au maximum un enregistrement) DEVRAIT avoir été exécutée préalablement.
	 * Retourne null si aucun recordset ou recordset vide.
	 *
	 * @param string $className La classe donnée.
	 * @return object|null L'instance de la classe donnée ou null.
	 */
	public function findOne(string $className = 'stdClass'): ?object
	{
		// Si pas de recordset, retourner null.
		if (!$this->stmt)
			return null;
		// Sinon, exploiter le recordset et retourner la première instance ou null.
		$this->stmt->setFetchMode(PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE, $className);
		return $this->stmt->fetch() ?: null;
	}

	/**
	 * Hydrate une instance donnée en exploitant le premier enregistrement du dernier jeu.
	 * Une requête SELECT (typiquement retrouvant au maximum un enregistrement) DEVRAIT avoir été exécutée préalablement.
	 *
	 * @param object $obj Instance donnée à hydrater.
	 * @return boolean True si l'hydratation a réussi.
	 */
	public function into(object $obj): bool
	{
		// Si pas de recordset, retourner false.
		if (!$this->stmt)
			return false;
		// Sinon, exploiter le recordset et hydrater l'instance.
		$this->stmt->setFetchMode(PDO::FETCH_INTO, $obj);
		return (bool) $this->stmt->fetch();
	}

	/**
	 * Retourne la dernière PK auto-incrémentée.
	 *
	 * @return integer PK
	 */
	public function pk(): int
	{
		return (int) $this->db->lastInsertId();
	}

	/**
	 * Démarre une transaction.
	 * Retourne $this pour chaînage.
	 *
	 * @return static $this pour chaînage.
	 */
	public function start(): static
	{
		$this->db->beginTransaction();
		return $this;
	}

	/**
	 * Définit un point de restauration dans la transaction en cours.
	 * Retourne $this pour chaînage.
	 *
	 * @param string $label Nom du point de restauration.
	 * @return static $this pour chaînage.
	 */
	public function savepoint(string $label): static
	{
		$q = "SAVEPOINT {$label}";
		return $this->xeq($q);
	}

	/**
	 * Effectue un rollback au point de restauration donné ou au départ si absent.
	 * Retourne $this pour chaînage.
	 *
	 * @param string|null $label Nom du point de restauration (optionnel).
	 * @return static $this pour chaînage.
	 */
	public function rollback(?string $label = null): static
	{
		$q = "ROLLBACK";
		if ($label)
			$q .= "TO {$label}";
		return $this->xeq($q);
	}

	/**
	 * Valide la transaction en cours.
	 * Retourne $this pour chaînage.
	 *
	 * @return static $this pour chaînage.
	 */
	public function commit(): static
	{
		$this->db->commit();
		return $this;
	}
}

<?php

declare(strict_types=1);

namespace entities;

use peps\core\ORMDB;
use peps\core\UserLoggable;

/**
 * Entité User.
 * Toutes les propriétés à null par défaut pour les formulaires de saisie.
 * 
 * @see ORMDB
 * @see UserLoggable
 * @copyright 2020-2022 Gilles VANDERSTRAETEN gillesvds@adok.info
 */
class User extends ORMDB implements UserLoggable
{
	/**
	 * PK.
	 */
	public ?int $idUser = null;

	/**
	 * Identifiant de connexion.
	 */
	public ?string $log = null;

	/**
	 * Mot de passe de connexion.
	 * Toujours chiffré !
	 */
	public ?string $pwd = null;

	/**
	 * Nom.
	 */
	public ?string $lastName = null;

	/**
	 * Prénom.
	 */
	public ?string $firstName = null;

	/**
	 * Instance de l'utilisateur en session.
	 * En cache pour lazy loading.
	 */
	protected static ?self $userSession = null;

	/**
	 * Constructeur.
	 */
	public function __construct(int $idUser = null)
	{
		$this->idUser = $idUser;
	}

	/**
	 * {@inheritDoc}
	 */
	public function login(string $clearPwd): bool
	{
		// Si identifiant ou mot de passe clair non renseignés, retourner false.
		if (!$this->log || !$clearPwd)
			return false;
		// Si aucun utilisateur correspondant à l'identifiant, retourner false.
		if (!$user = self::findOneBy(['log' => $this->log]))
			return false;
		// Si mot de passe incorrect, retourner false.
		if (!password_verify($clearPwd, $user->pwd))
			return false;
		// Inscrire l'utilisateur dans la session et retourner true.
		$_SESSION['idUser'] = $user->idUser;
		return true;
	}

	/**
	 * {@inheritDoc}
	 */
	public static function getUserSession(): ?self
	{
		// Si pas en cache, créer et hydrater l'utilisateur en session.
		if (!self::$userSession) {
			// Créer une instance.
			$user = new self($_SESSION['idUser'] ?? null);
			// Si user et hydratation réussie, stocker l'instance dans le cache.
			self::$userSession = $user && $user->hydrate() ? $user : null;
		}
		// Retourner l'utilisateur en session.
		return self::$userSession;
	}
}

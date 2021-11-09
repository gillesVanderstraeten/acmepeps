<?php

declare(strict_types=1);

namespace peps\core;

/**
 * Interface de connexion des utilisateurs.
 * 
 * @copyright 2020-2022 Gilles VANDERSTRAETEN gillesvds@adok.info
 */
interface UserLoggable
{
	/**
	 * Tente de loguer le UserLoggable.
	 *
	 * @param string $clearPwd Mot de passe clair.
	 * @return boolean True ou false selon que le UserLoggable a été logué ou pas.
	 */
	function login(string $clearPwd): bool;

	/**
	 * Retourne le UserLoggable en session ou null si aucun en session.
	 * DEVRAIT utiliser le lazy loading.
	 *
	 * @return self|null UserLoggable en session ou null.
	 */
	static function getUserSession(): ?self;
}

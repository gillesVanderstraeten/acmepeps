<?php

declare(strict_types=1);

namespace peps\core;

use Exception;

/**
 * Classe 100% statique d'autoload.
 * 
 * @copyright 2020-2022 Gilles VANDERSTRAETEN gillesvds@adok.info
 */
final class Autoload
{
	/**
	 * Constructeur privé.
	 */
	private function __construct()
	{
	}

	/**
	 * Initialise l'autoload.
	 * DOIT être appelée depuis le contrôleur frontal EN TOUT PREMIER.
	 * @include pour bypasser les chemins virtuels d'éventuels archives PHAR (PHPUnit...).
	 * 
	 * @throws Exception Si variable serveur SCRIPT_FILENAME indéfinie.
	 */
	public static function init(): void
	{
		// Inscrire la fonction d'autolad dans la pile d'autoload.
		$scriptFilename = filter_input(INPUT_SERVER, 'SCRIPT_FILENAME', FILTER_SANITIZE_STRING) ?: filter_var($_SERVER['SCRIPT_FILENAME'], FILTER_SANITIZE_STRING);
		if (!$scriptFilename)
			throw new Exception("Server variable SCRIPT_FILENAME undefined.");
		$path = mb_substr($scriptFilename, 0, mb_strlen($scriptFilename) - 9);
		spl_autoload_register(fn ($className) => @include strtr($path . strtr($className, '\\', DIRECTORY_SEPARATOR), DIRECTORY_SEPARATOR . DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR) . '.php');
	}
}

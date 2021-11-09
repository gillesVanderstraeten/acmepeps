<?php

declare(strict_types=1);

namespace cfg;

use peps\core\Cfg;

/**
 * Classe 100% statique de configuration générale de l'application.
 * DOIT être étendue par une classe finale par serveur.
 * 
 * @see Cfg
 * @copyright 2020-2022 Gilles VANDERSTRAETEN gillesvds@adok.info
 */
class CfgApp extends Cfg
{
	/**
	 * Constructeur privé.
	 */
	private function __construct()
	{
	}

	/**
	 * Initialise la configuration.
	 * PROTECTED parce que classes enfants présentes.
	 */
	protected static function init(): void
	{
		// Inscrire les constantes de la classe parente.
		parent::init();

		// Titre de l'application.
		self::register('appTitle', "ACME");

		// Poids maxi d'une photo (octets).
		self::register('imgMaxFileSize', 10 * 1024 * 1024); // 10 MB

		// Tableau des types MIME autorisés pour la photo. Vide si tous types autorisés.
		self::register('imgAllowedMimeTypes', ['image/jpeg']);

		// Largeur du cadre de destination des images (pixels).
		self::register('imgWidth', 450);

		// Hauteur du cadre de destination des images (pixels).
		self::register('imgHeight', 450);
	}
}

<?php

declare(strict_types=1);

namespace peps\core;

use Locale;
use NumberFormatter;

/**
 * Classe 100% statique de configuration initiale de l'application.
 * DOIT être étendue dans l'application par une classe de configuration générale elle-même étendue par une classe finale par serveur.
 * Extension PHP intl requise.
 * 
 * @copyright 2020-2022 Gilles VANDERSTRAETEN gillesvds@adok.info
 */
class Cfg
{
	/**
	 * Tableau associatif des constantes de configuration.
	 * 
	 * @var mixed[]
	 */
	private static array $constants = [];

	/**
	 * Constructeur privé.
	 */
	private function __construct()
	{
	}

	/**
	 * Inscrit les constantes de base.
	 * DOIT être redéfinie dans la classe enfant pour y inscrire les constantes de l'application en invoquant parent::init() en première instruction.
	 * Cette méthode doit restée PROTECTED sauf au dernier niveau d'héritage dans lequel elle DOIT être PUBLIC pour être invoquée depuis le contrôleur frontal.
	 * Les clés (en SNAKE_CASE) enregistrées ici sont LES SEULES accessibles aux classes PEPS.
	 * Les clés ajoutées par l'application DEVRAIENT être en camelCase.
	 */
	protected static function init(): void
	{
		// Chemin du fichier JSON des routes depuis la racine de l'application.
		self::register('ROUTE_FILE', 'cfg' . DIRECTORY_SEPARATOR . 'routes.json');

		// Namespace des contrôleurs.
		self::register('CONTROLLERS_NAMESPACE', 'controllers');

		// Chemin du répertoire des vues depuis la racine de l'application.
		self::register('VIEWS_DIR', 'views');

		// Nom de la vue affichant l'erreur 404.
		self::register('ERROR_404_VIEW', 'error404.php');

		// Locale par défaut en cas de non détection (ex: 'fr' ou 'fr-FR').
		self::register('LOCALE_DEFAULT', 'fr');

		// Locale du client.
		self::register('LOCALE', (function () {
			// Récupérer les locales du client.
			$locales = filter_input(INPUT_SERVER, 'HTTP_ACCEPT_LANGUAGE', FILTER_SANITIZE_STRING) ?: filter_var($_SERVER['HTTP_ACCEPT_LANGUAGE'], FILTER_SANITIZE_STRING);
			return $locales ? Locale::acceptFromHttp($locales) : self::$constants['LOCALE_DEFAULT'];
		})());

		// Instance de NumberFormatter pour formater un nombre avec 2 décimales selon la locale.
		self::register('NF_LOCALE_2DEC', (fn () => NumberFormatter::create(self::$constants['LOCALE'], NumberFormatter::PATTERN_DECIMAL, '#,##0.00'))());

		// Constante du mode PERSISTENT des sessions.
		self::register('SESSION_PERSISTENT', 'SESSION_PERSISTENT');

		// Constante du mode HYBRID des sessions.
		self::register('SESSION_HYBRID', 'SESSION_HYBRID');

		// Constante du mode ABSOLUTE des sessions.
		self::register('SESSION_ABSOLUTE', 'SESSION_ABSOLUTE');

		// Constante de l'option STRICT de "cookie_samesite" des sessions.
		self::register('COOKIE_SAMESITE_STRICT', 'STRICT');

		// Constante de l'option LAX de "cookie_samesite" des sessions.
		self::register('COOKIE_SAMESITE_LAX', 'LAX');

		// Constante de l'option NONE de "cookie_samesite" des sessions.
		self::register('COOKIE_SAMESITE_NONE', 'NONE');
	}

	/**
	 * Inscrit une constante (paire clé/valeur) dans le tableau des constantes.
	 *
	 * @param string $key Clé.
	 * @param mixed $val Valeur.
	 */
	 protected final static function register(string $key, mixed $val = null): void
	{
		self::$constants[$key] = $val;
	}

	/**
	 * Retourne la valeur de la constante à partir de sa clé.
	 * Retourne null si clé inexistante.
	 * 
	 * @param string $key Clé.
	 * @return mixed Valeur.
	 */
	 public final static function get(string $key): mixed
	{
		return self::$constants[$key] ?? null;
	}
}

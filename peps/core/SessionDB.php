<?php

declare(strict_types=1);

namespace peps\core;

use PDOException;
use SessionHandlerInterface;

/**
 * Gestion des sessions en DB.
 * NECESSITE une table "session" avec les colonnes "sid", "data", "dateSession".
 * 3 modes possibles :
 *   PERSISTENT: La session se termine exclusivement après l'expiration du timeout au-delà de la dernière requête du client.
 *   HYBRID: La session se termine à la fermeture du navigateur OU après l'expiration du timeout au-delà de la dernière requête du client.
 *   ABSOLUTE: La session se termine exclusivement après l'expiration du timeout au-delà de la PREMIERE requête du client.
 * 
 * @see SessionHandlerInterface
 * @copyright 2020-2022 Gilles VANDERSTRAETEN gillesvds@adok.info
 */
class SessionDB implements SessionHandlerInterface
{
	/**
	 * Vrai si session périmée.
	 */
	protected static bool $expired = false;

	/**
	 * Durée maxi de la session (secondes).
	 */
	protected static int $timeout;

	/**
	 * Initialise et démarre la session.
	 *
	 * @param integer $timeout Durée maxi de la session (secondes).
	 * @param string $mode Mode de la session (PERSISTENT | HYBRID | ABSOLUTE).
	 * @param string $sameSite Mitigation CSRF.
	 */
	public static function init(int $timeout, string $mode, string $sameSite): void
	{
		// Définir le timeout.
		self::$timeout = $timeout;
		// Définir la durée de vie du cookie en fonction du mode.
		match ($mode) {
			Cfg::get('SESSION_PERSISTENT') => ini_set('session.cookie_lifetime', (string) (86400 * 365 * 20)), // 20 ans (expiration gérée côté serveur).
			Cfg::get('SESSION_HYBRID') => ini_set('session.cookie_lifetime', '0'), // Cookie de session.
			Cfg::get('SESSION_ABSOLUTE') => ini_set('session.cookie_lifetime', (string) $timeout) // Cookie à durée limitée au timeout.
		};
		// Définir le timeout de GC pour supprimer les sessions expirées.
		ini_set('session.gc_maxlifetime', (string) $timeout);
		// Utiliser les cookies.
		ini_set('session.use_cookies', '1');
		// Utiliser seulement les cookies.
		ini_set('session.use_only_cookies', '1');
		// Ne pas passer l'ID de session en GET.
		ini_set('session.use_trans_sid', '0');
		// Mitiger les attaques XSS (Cross Site Scripting = injections) en interdisant l'accès aux cookies via JS.
		ini_set('session.cookie_httponly', '1');
		// Mitiger les attaques SFA (Session Fixation Attack) en refusant les cookies non générés par PHP.
		ini_set('session.use_strict_mode', '1');
		// Mitiger les attaques CSRF (Cross Site Request Forgery).
		ini_set('session.cookie_samesite', $sameSite);
		// Définir une instance de cette classe comme gestionnaire des sessions.
		session_set_save_handler(new self());
		// Démarrer la session.
		//var_dump("SessionDB.init() : ABOUT TO START");
		session_start();
		//var_dump("SessionDB.init() : STARTED");
		// Si read() a détecté une session expirée, la détruire et en démarrer une nouvelle.
		if (self::$expired) {
			//var_dump("SessionDB.init() : ABOUT TO DESTROY");
			session_destroy();
			//var_dump("SessionDB.init() : DESTROYED");
			self::$expired = false;
			//var_dump("SessionDB.init() : ABOUT TO RESTART");
			session_start();
			//var_dump("SessionDB.init() : RESTARTED");
		}
	}

	/**
	 * Inutile ici.
	 *
	 * @param string $path Chemin du fichier de sauvegarde de la session.
	 * @param string $name Nom de la session (PHPSESSID par défaut).
	 * @return boolean Pour usage interne PHP, ici systématiquement true.
	 */
	public function open($path, $name): bool
	{
		//var_dump("SessionDB.open({$path}, {$name})");
		return true;
	}

	/**
	 * Lit et retourne les données de session.
	 *
	 * @param string $id SID.
	 * @return string|false Données de session sérialisées (PHP) ou false si lecture impossible.
	 */
	public function read($id): string|false
	{
		// Créer la requête.
		$q = "SELECT * FROM session WHERE sid = :sid";
		$params = [':sid' => $id];
		// Exécuter la requête.
		$objSession = DBAL::get()->xeq($q, $params)->findOne();
		// Si session présente mais expirée, définir $expired à true et retourner une chaîne vide.
		if ($objSession && strtotime($objSession->dateSession) + self::$timeout < time()) {
			//var_dump("SessionDB.read({$id}) : EXPIRED");
			self::$expired = true;
			return '';
		}
		// Si session présente, retourner les données. Sinon, retourner une chaîne vide.
		if ($objSession) {
			//var_dump("SessionDB.read({$id}) : FOUND");
			return $objSession->data;
		}
		//var_dump("SessionDB.read({$id}) : NOT FOUND");
		return '';
	}

	/**
	 * Ecrit les données de session.
	 *
	 * @param string $id SID.
	 * @param string $data Données de session.
	 * @return boolean Pour usage interne PHP, ici systématiquement true.
	 */
	public function write($id, $data): bool
	{
		if (!self::$expired) {
			// Tenter une requête INSERT.
			try {
				$q = "INSERT INTO session VALUES(:sid, :data, :dateSession)";
				$params = [':sid' => $id, ':data' => $data, ':dateSession' => date('Y-m-d H:i:s')];
				DBAL::get()->xeq($q, $params);
				//var_dump("SessionDB.write({$id}) : INSERT {$data}");
			}
			// Si erreur (doublon de SID), exécuter une requête UPDATE.
			catch (PDOException $e) {
				$q = "UPDATE session SET data = :data, dateSession = :dateSession WHERE sid = :sid";
				$params = [':sid' => $id, ':data' => $data, ':dateSession' => date('Y-m-d H:i:s')];
				DBAL::get()->xeq($q, $params);
				//var_dump("SessionDB.write({$id}) : UPDATE {$data}");
			}
		}
		// Retourner systématiquement true.
		return true;
	}

	/**
	 * Inutile ici.
	 *
	 * @return boolean Pour usage interne PHP, ici systématiquement true.
	 */
	public function close(): bool
	{
		//var_dump("SessionDB.close()");
		return true;
	}

	/**
	 * Détruit la session (cookie uniquement, l'enregistrement en DB sera supprimé par GC).
	 *
	 * @param string $id SID.
	 * @return boolean Pour usage interne PHP, ici systématiquement true.
	 */
	public function destroy($id): bool
	{
		//var_dump("SessionDB.destroy({$id})");
		// Récupérer le nom de la session.
		$sessionName = session_name();
		// Supprimer le cookie du navigateur.
		setcookie($sessionName, '', 1, '/');
		// Supprimer la clé du tableau des cookies du serveur.
		unset($_COOKIE[$sessionName]);
		// Retourner systématiquement true.
		return true;
	}

	/**
	 * Garbage Collector, supprime les sessions expirées en DB.
	 *
	 * @param int $max_lifetime Durée de vie maxi d'une session (secondes).
	 * @return integer|false True si la suppression a réussi, false sinon.
	 */
	public function gc($max_lifetime): int|false
	{
		//var_dump("SessionDB.gc({$max_lifetime})");
		// Créer la requête.
		$q = "DELETE FROM session WHERE dateSession < :dateMin";
		$params = [':dateMin' => date('Y-m-d H:i:s', time() - $max_lifetime)];
		// Retourner le nombre d'enregistrements supprimés.
		return DBAL::get()->xeq($q, $params)->nb();
	}
}

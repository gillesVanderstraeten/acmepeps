<?php

declare(strict_types=1);

namespace peps\core;

/**
 * Classe 100% statique de routage.
 * Offre 5 méthodes de routage:
 *   render(): rendre une vue.
 *   text(): envoyer du texte brut (text/plain).
 *   json(): envoyer du JSON (application/json).
 *   download(): envoyer un fichier en flux binaire.
 *   redirect(): rediriger côté client.
 * Toutes ces méthodes ARRETENT l'exécution.
 * 
 * @copyright 2020-2022 Gilles VANDERSTRAETEN gillesvds@adok.info
 */
final class Router
{
	/**
	 * Constructeur privé.
	 */
	private function __construct()
	{
	}

	/**
	 * Routage initial.
	 * Analyse la requête du client, détermine la route et invoque la méthode appropriée du contrôleur approprié.
	 */
	public static function route(): void
	{
		// Récupérer le verbe HTTP et l'URI de la requête client.
		$verb = filter_input(INPUT_SERVER, 'REQUEST_METHOD', FILTER_SANITIZE_STRING) ?: filter_var($_SERVER['REQUEST_METHOD'], FILTER_SANITIZE_STRING);
		$uri = filter_input(INPUT_SERVER, 'REQUEST_URI', FILTER_SANITIZE_STRING) ?: filter_var($_SERVER['REQUEST_URI'], FILTER_SANITIZE_STRING);
		// Si pas de verbe ou d'URI, rendre la vue 404.
		if (!$verb || !$uri)
			self::render(Cfg::get('ERROR_404_VIEW'));
		// Charger la table de routage JSON.
		$routes = json_decode(file_get_contents(Cfg::get('ROUTE_FILE')));
		// Parcourir la table de routage.
		foreach ($routes as $route) {
			// Utiliser l'expression régulière de l'URI avec un slash final optionnel.
			$regexp = "@^{$route->uri}/?$@";
			// Si une route correspondante est trouvée...
			if (!strcasecmp($route->verb, $verb) && preg_match($regexp, $uri, $matches)) {
				// Supprimer le premier élément du tableau.
				array_shift($matches);
				// Si paramètres, utiliser les noms fournis (si disponibles) pour obtenir un tableau associatif, sinon un tableau indicé.
				if (($assocParams = $matches) && !empty($route->params))
					@$assocParams = array_combine($route->params, $matches) ?: $matches;
				// Séparer le nom du contrôleur du nom de la méthode.
				[$controllerName, $methodName] = explode('.', $route->method);
				// Préfixer le nom du contrôleur avec son namespace (pas de "use").
				$controllerName = Cfg::get('CONTROLLERS_NAMESPACE') . '\\' . $controllerName;
				// Invoquer la méthode du contrôleur.
				$controllerName::$methodName($assocParams);
				return;
			}
		}
		// Si aucune route trouvée, rendre la vue 404.
		self::render(Cfg::get('ERROR_404_VIEW'));
	}

	/**
	 * Rend une vue puis arrête l'exécution.
	 * 
	 * @param string $view Nom de la vue (ex: 'test.php').
	 * @param array $params Tableau associatif des paramètres à transmettre à la vue.
	 */
	public static function render(string $view, array $params = []): void
	{
		// Transformer chaque clé en variables.
		extract($params);
		// Insérer la vue.
		require Cfg::get('VIEWS_DIR') . DIRECTORY_SEPARATOR . $view;
		// Arrêter l'exécution pour envoyer la vue vers le client.
		exit;
	}

	/**
	 * Envoie au client une chaîne JSON puis arrête l'exécution.
	 *
	 * @param string $json Chaîne JSON.
	 */
	public static function json(string $json): void
	{
		// Paramétrer l'entête HTTP du JSON.
		header('Content-Type:application/json');
		// Envoyer la chaîne JSON au client puis arrêter l'exécution.
		exit($json);
	}

	/**
	 * Envoie au client un fichier pour download (ou intégration comme par exemple une image) puis arrête l'exécution.
	 *
	 * @param string $filePath Chemin complet du fichier depuis la racine de l'application.
	 * @param string $mimeType Type MIME du fichier.
	 * @param string $fileName Nom du fichier proposé au client.
	 */
	public static function download(string $filePath, string $mimeType, string $fileName = "File"): void
	{
		// Paramétrer l'entête HTTP.
		header("Content-Type:{$mimeType}");
		header('Content-Transfer-Encoding:Binary');
		header('Content-Length:' . filesize($filePath));
		header("Content-Disposition:attachment; filename={$fileName}");
		// Lire le fichier et l'envoyer vers le client.
		readfile($filePath);
		// Arrêter l'exécution.
		exit;
	}

	/**
	 * Redirige côté client.
	 * Envoie la requête vers le client pour demander une redirection vers une URI puis arrête l'exécution.
	 *
	 * @param string $uri URI
	 */
	public static function redirect(string $uri): void
	{
		// Envoyer la demande de redirection vers le client.
		header("Location:{$uri}");
		// Arrêter l'exécution.
		exit;
	}
}

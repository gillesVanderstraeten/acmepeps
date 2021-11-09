<?php

declare(strict_types=1);

// Classe d'autoload : unique 'require' de l'application, indispensable en tout premier.
require 'peps/core/Autoload.php';

use cfg\CfgLocal;
use cfg\CfgNuxit;
use peps\core\Autoload;
use peps\core\Cfg;
use peps\core\DBAL;
use peps\core\Router;
use peps\core\SessionDB;

// *****************************************************************
// Contrôleur frontal de l'application.
// Copyright (c) 2020-2022 Gilles VANDERSTRAETEN gillesvds@adok.info
// *****************************************************************

// DEBUG
// echo '<pre>';
// var_dump($_SERVER);
// echo '</pre>';

// Initialiser l'autoload (à faire EN PREMIER).
Autoload::init();

// Initialiser la configuration (à faire en DEUXIEME).
// Récupérer l'adresse IP du serveur.
$serverAddr = filter_input(INPUT_SERVER, 'SERVER_ADDR', FILTER_VALIDATE_IP) ?: filter_var($_SERVER['SERVER_ADDR'], FILTER_VALIDATE_IP);
// Si pas d'IP, lever une exception.
if (!$serverAddr)
	throw new Exception("Server variable SERVER_ADDR undefined.");
// ICI, VOS CLASSES DE CONFIGURATION EN FONCTION DES ADRESSES IP DE VOS SERVEURS.
(match ($serverAddr) {
	'::1' => CfgLocal::class, // Local
})::init();

// Initialiser la connexion DB (à faire AVANT d'initialiser SessionDB).
DBAL::init(
	Cfg::get('dbDriver'),
	Cfg::get('dbHost'),
	Cfg::get('dbPort'),
	Cfg::get('dbName'),
	Cfg::get('dbLog'),
	Cfg::get('dbPwd'),
	Cfg::get('dbCharset')
);

// Initialiser la gestion des sessions (à faire APRES l'initialisation de la connexion DB).
SessionDB::init(
	Cfg::get('sessionTimeout'),
	Cfg::get('sessionMode'),
	Cfg::get('cookieSameSite')
);

// Router la requête du client (à faire EN DERNIER).
Router::route();

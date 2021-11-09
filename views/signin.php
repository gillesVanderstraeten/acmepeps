<?php

declare(strict_types=1);

namespace views;

use peps\core\Cfg;

?>
<!DOCTYPE html>
<html lang="fr">

<head>
	<meta charset="UTF-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title><?= Cfg::get('appTitle') ?></title>
	<link rel="stylesheet" href="/assets/css/acme.css" />
</head>

<body>
	<header></header>
	<main>
		<div class="category">
			<a href="/">Accueil</a> &gt; Connexion
		</div>
		<div class="error"><?= implode('<br/>', $errors ?? []) ?></div>
		<form name="form1" action="/user/login" method="POST">
			<div class="item">
				<label>Identifiant</label>
				<input name="log" value="<?= $log ?>" size="10" maxlength="10" required="required" />
			</div>
			<div class="item">
				<label>Mot de passe</label>
				<input type="password" name="pwd" size="10" maxlength="10" required="required" />
			</div>
			<div class="item">
				<label></label>
				<input type="submit" value="Connexion" />
			</div>
		</form>
	</main>
	<footer></footer>
</body>

</html>
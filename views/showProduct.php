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
	<?php require 'views/inc/header.php' ?>
	<main>
		<div class="category">
			<a href="/product/list">Produits</a> &gt; <?= $product->name ?>
		</div>
		<div id="detailProduct">
			<img src="/assets/img/products/product_<?= $product->idImg ?? 0 ?>.jpg?mtime=<?= $mtime ?? 0 ?>" alt="<?= $product->name ?>" />
			<div>
				<div class="price"><?= Cfg::get('NF_LOCALE_2DEC')->format($product->price) ?></div>
				<div class="category">catégorie<br />
					<?= $product->category->name ?></div>
				<div class="ref">référence<br />
					<?= $product->ref ?></div>
			</div>
		</div>
	</main>
	<footer></footer>
</body>

</html>
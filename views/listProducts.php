<?php

declare(strict_types=1);

namespace views;

use entities\User;
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
		<?php
		foreach ($categories as $category) {
		?>
			<div class="category">
				<?php
				if (User::getUserSession()) {
				?>
					<a href="/product/create/<?= $category->idCategory ?>">
						<img class="ico" src="/assets/img/ico/create.svg" alt="Add a product in this category" />
					</a>
				<?php
				}
				?>
				<?= $category->name ?>
			</div>
			<?php
			foreach ($category->products as $product) {
			?>
				<div class="blockProduct">
					<a href="/product/show/<?= $product->idProduct ?>">
						<img class="thumbnail" src="/assets/img/products/product_<?= $product->idImg ?? 0 ?>.jpg?mtime=<?= $mtime ?? 0 ?>" alt="<?= $product->name ?>" />
						<div class="name"><?= $product->name ?></div>
					</a>
					<?php
					if (User::getUserSession()) {
					?>
						<a class="ico update" href="/product/update/<?= $product->idProduct ?>">
							<img src="/assets/img/ico/update.svg" alt="Edit the product" />
						</a>
						<img class="ico delete" src="/assets/img/ico/delete.svg" onclick="deleteAll(<?= $product->idProduct ?>)" alt="Delete the product" />
						<img class="ico deleteImg" src="/assets/img/ico/deleteImg.svg" onclick="deleteImg(<?= $product->idProduct ?>)" alt="Delete the image" />
					<?php
					}
					?>
				</div>
		<?php
			}
		}
		?>
	</main>
	<footer></footer>
	<script src="/assets/js/listProducts.js"></script>
</body>

</html>
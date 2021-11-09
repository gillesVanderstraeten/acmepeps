<?php

declare(strict_types=1);

use peps\core\Cfg;
?>
<!DOCTYPE html>
<html lang="fr">

<head>
	<meta charset="UTF-8">
	<title><?= Cfg::get('appTitle') ?></title>
	<link rel="stylesheet" href="/assets/css/acme.css">
</head>

<body>
	<?php require_once 'inc/header.php' ?>
	<main>
		<div class="category">
			<a href="/product/list">Produits</a> &gt; Editer
		</div>
		<div class="error"><?= implode('<br />', $errors ?? []) ?></div>
		<form name="form1" action="/product/save" method="POST" enctype="multipart/form-data">
			<input type="hidden" name="idProduct" value="<?= $product->idProduct ?>" />
			<div class="item">
				<label>Catégorie</label>
				<select name="idCategory">
					<?php
					foreach ($categories as $category) {
						$selected = $category->idCategory === $product->idCategory ? 'selected="selected"' : '';
					?>
						<option value="<?= $category->idCategory ?>" <?= $selected ?>><?= $category->name ?></option>
					<?php
					}
					?>
				</select>
			</div>
			<div class="item">
				<label>Nom</label>
				<input name="name" value="<?= $product->name ?>" size="20" maxlength="50" required="required" />
			</div>
			<div class="item">
				<label>Référence</label>
				<input name="ref" value="<?= $product->ref ?>" size="10" maxlength="10" required="required" />
			</div>
			<div class="item">
				<label>Prix</label>
				<input type="number" name="price" value="<?= $product->price ?>" min="0.01" max="9999.99" step="0.01" size="7" maxlength="7" required="required" />
			</div>
			<div class="item">
				<label>Photo (JPEG)</label>
				<input type="file" name="photo" onchange="displayPhoto(this.files)" accept="<?= join(',', Cfg::get('imgAllowedMimeTypes')) ?>" />
				<input type="button" value="Parcourir..." onclick="this.form.photo.click()" />
			</div>
			<div class="item">
				<label></label>
				<a href="/product/list"><input type="button" value="Annuler" /></a>
				<input type="submit" name="submit" value="Valider" />
			</div>
		</form>
		<div id="thumbnail">
			<img src="/assets/img/products/product_<?= $product->idImg ?? 0 ?>.jpg?mtime=<?= $mtime ?? 0 ?>" alt="<?= $product->name ?>" />
		</div>
	</main>
	<footer></footer>
	<script>
		const IMG_MAX_FILE_SIZE = <?= Cfg::get('imgMaxFileSize') ?>;
		const IMG_ALLOWED_MIME_TYPES = <?= json_encode(Cfg::get('imgAllowedMimeTypes'), JSON_UNESCAPED_SLASHES) ?>;
	</script>
	<script src="/assets/js/editProduct.js"></script>
</body>

</html>
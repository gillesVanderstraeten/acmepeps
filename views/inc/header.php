<?php

declare(strict_types=1);

namespace views\inc;

use entities\User;
?>
<header>
	<div class="user">
		<?php
		if (User::getUserSession()) {
		?>
			<?= User::getUserSession()->lastName ?> <?= User::getUserSession()->firstName ?>
			&nbsp;&nbsp;&nbsp;
			[ <a href="/user/logout">Déconnexion</a> ]
		<?php
		} else {
		?>
			[ <a href="/user/signin">Connexion</a> ]
		<?php
		}
		?>
	</div>
</header>

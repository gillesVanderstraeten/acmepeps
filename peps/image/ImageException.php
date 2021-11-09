<?php

declare(strict_types=1);

namespace peps\image;

use Exception;

/**
 * Exceptions en lien avec Image.
 * Classe 100% statique.
 * 
 * @see Exception
 * @see Image
 * @copyright 2020-2022 Gilles VANDERSTRAETEN gillesvds@adok.info
 */
class ImageException extends Exception
{
	// Messages d'erreur.
	public const UNREADABLE_IMAGE = "La lecture du fichier image a échoué.";
	public const IMAGE_COPY_FAILED = "La copie de l'image a échoué.";
	public const IMAGE_RESIZING_FAILED = "Le redimensionnement de l'image a échoué.";
	public const TARGET_RESOURCE_CREATION_FAILED = "La création de la ressource GdImage cible a échoué.";
}

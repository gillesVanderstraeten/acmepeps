<?php

declare(strict_types=1);

namespace peps\image;

/**
 * Exceptions en lien avec ImageJpeg.
 * Classe 100% statique.
 * 
 * @see ImageException
 * @see ImageJpeg
 * @copyright 2020-2022 Gilles VANDERSTRAETEN gillesvds@adok.info
 */
final class ImageJpegException extends ImageException
{
	// Messages d'erreur.
	public const IMAGE_NOT_JPEG = "Le type de l'image n'est pas JPEG.";
	public const RESOURCE_FROM_JPEG_CREATION_FAILED = "La création de la ressource GdImage à partir de l'image JPEG a échoué.";
	public const JPEG_FROM_RESOURCE_CREATION_FAILED = "La création de l'image JPEG à partir de la ressource GdImage a échoué.";
}

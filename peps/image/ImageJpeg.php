<?php

declare(strict_types=1);

namespace peps\image;

use GdImage;

/**
 * Implémentation de Image pour le type JPEG.
 * 
 * @see Image
 * @see ImageJpegException
 * @copyright 2020-2022 Gilles VANDERSTRAETEN gillesvds@adok.info
 */
final class ImageJpeg extends Image
{
	/**
	 * Qualité (1 à 100).
	 */
	private int $quality;

	/**
	 * Constructeur.
	 * 
	 * @param string $path Chemin du fichier JPEG source.
	 * @param int $quality Qualité (60 par défaut).
	 */
	public function __construct(string $path, int $quality = 60)
	{
		// Appeler le constructeur parent.
		parent::__construct($path);
		// Définir les propriétés propres au type JPEG.
		$this->quality = $quality;
	}

	/**
	 * {@inheritDoc}
	 */
	public function getMimeType(): string
	{
		return 'image/jpeg';
	}

	/**
	 * {@inheritDoc}
	 */
	protected function from(): GdImage
	{
		// Si la création de la ressource GdImage à partir du fichier échoue, déclencher une exception.
		if (!($gdSource = imagecreatefromjpeg($this->path)))
			throw new ImageJpegException(ImageJpegException::RESOURCE_FROM_JPEG_CREATION_FAILED);
		// Retourner la ressource GdImage.
		return $gdSource;
	}

	/**
	 * {@inheritDoc}
	 */
	protected function to($gdImage, string $targetPath): void
	{
		// Si la création du fichier depuis la ressource échoue, déclencher une exception.
		if (!imagejpeg($gdImage, $targetPath, $this->quality))
			throw new ImageJpegException(ImageJpegException::JPEG_FROM_RESOURCE_CREATION_FAILED);
	}
}

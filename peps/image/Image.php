<?php

declare(strict_types=1);

namespace peps\image;

use Error;
use GdImage;

/**
 * Représente un fichier image indépendamment de son type et permet de le redimensionner.
 * Extension PHP gd2 requise.
 * 
 * @see GdImage
 * @see ImageException
 * @copyright 2020-2022 Gilles VANDERSTRAETEN gillesvds@adok.info
 */
abstract class Image
{
	/**
	 * Chemin absolu du fichier image.
	 */
	protected ?string $path = null;

	/**
	 * Largeur de l'image en pixels.
	 */
	protected ?int $width = null;

	/**
	 * Hauteur de l'image en pixels.
	 */
	protected ?int $height = null;

	/**
	 * Constructeur PROTECTED.
	 * Les classes enfants DOIVENT avoir un constructeur PUBLIC qui DEVRAIT appeler ce constructeur parent.
	 * Vérifie le type MIME du fichier image et récupère ses dimensions.
	 * Déclenche une ImageException en cas d'erreur.
	 * 
	 * @param string $path Chemin du fichier image.
	 * @throws ImageException Si chemin ou type MIME invalides.
	 */
	protected function __construct(string $path)
	{
		// Récupérer le type MIME du fichier.
		@$mimeType = mime_content_type($path); // @ pour éviter le warning.
		// Si type différent du type attendu, déclencher une exception.
		if ($mimeType !== $this->getMimeType())
			throw new ImageException(ImageException::UNREADABLE_IMAGE);
		// Récupérer les dimensions de l'image.
		try {
			[$this->width, $this->height] = getimagesize($path);
		} catch (Error $e) {
			throw new ImageException(ImageException::UNREADABLE_IMAGE);
		}
		// Affecter le chemin.
		$this->path = $path;
	}

	/**
	 * Retourne le type MIME dédié de la classe enfant.
	 * 
	 * @return string Type MIME.
	 */
	public abstract function getMimeType(): string;

	/**
	 * Accès public en lecture seule à la largeur.
	 *
	 * @return integer|null Largeur en pixels.
	 */
	public function getWidth(): ?int
	{
		return $this->width;
	}

	/**
	 * Accès public en lecture seule à la hauteur.
	 *
	 * @return integer|null Hauteur en pixels.
	 */
	public function getHeight(): ?int
	{
		return $this->height;
	}

	/**
	 * Crée un fichier image correspondant au redimensionnement de l'image pour l'inscrire dans un cadre donné.
	 * Copie simplement le fichier image source si le cadre est plus grand dans ses deux dimensions.
	 * Déclenche une ImageException en cas d'erreur.
	 *
	 * @param int $frameWidth Largeur du cadre.
	 * @param int $frameHeight Hauteur du cadre.
	 * @param string $targetPath Chemin complet du fichier image à créer.
	 * @throws ImageException Si erreur.
	 */
	public function copyResize(int $frameWidth, int $frameHeight, string $targetPath): void
	{
		// Calculer les ratios largeur/hauteur de l'Image source et du cadre.
		$sourceRatio = $this->width / $this->height;
		$frameRatio = $frameWidth / $frameHeight;
		// Si l'Image source est trop horizontale et sa largeur supérieure à celle du cadre...
		if ($sourceRatio > $frameRatio && $this->width > $frameWidth) {
			// Caler la largeur de l'image finale sur la largeur du cadre.
			$targetWidth = $frameWidth;
			// Déterminer la hauteur en conservant les proportions.
			$targetHeight = (int) ($targetWidth / $sourceRatio);
		}
		// Sinon, si l'Image source est trop verticale et sa hauteur supérieure à celle du cadre...
		elseif ($sourceRatio < $frameRatio && $this->height > $frameHeight) {
			// Caler la hauteur de l'image finale sur la hauteur du cadre.
			$targetHeight = $frameHeight;
			// Déterminer la largeur en conservant les proportions.
			$targetWidth = (int) ($targetHeight * $sourceRatio);
		}
		// Sinon, faire une simple copie et retourner.
		else {
			// Si la copie échoue, déclencher une exception.
			if (!copy($this->path, $targetPath))
				throw new ImageException(ImageException::IMAGE_COPY_FAILED);
			return;
		}
		// Créer la ressource GdImage source à partir de l'Image source.
		$gdSource = $this->from();
		// Créer la ressource GdImage cible.
		if (!($gdTarget = imagecreatetruecolor($targetWidth, $targetHeight)))
			throw new ImageException(ImageException::TARGET_RESOURCE_CREATION_FAILED);
		// Redimensionner la ressource GdImage source vers la ressource GdImage cible.
		if (!imagecopyresampled($gdTarget, $gdSource, 0, 0, 0, 0, $targetWidth, $targetHeight, $this->width, $this->height))
			throw new ImageException(ImageException::IMAGE_RESIZING_FAILED);
		// Libérer la ressource GdImage source.
		imagedestroy($gdSource);
		// Créer le fichier image cible.
		$this->to($gdTarget, $targetPath);
		// Libérer la ressource GdImage cible.
		imagedestroy($gdTarget);
	}

	/**
	 * Crée la ressource GdImage source à partir de l'Image source.
	 * Implémentation nécessaire en fonction du type MIME.
	 *
	 * @return resource Ressource GdImage créée.
	 */
	protected abstract function from(): GdImage;

	/**
	 * Crée le fichier image cible à partir de la ressource GdImage source.
	 * Implémentation nécessaire en fonction du type MIME.
	 *
	 * @param resource $target Ressource GdImage cible.
	 * @param string $targetPath Chemin complet du fichier image cible à créer.
	 */
	protected abstract function to($gdImage, string $targetPath): void;
}

<?php

declare(strict_types=1);

namespace peps\upload;

/**
 * Gestion des uploads de fichiers.
 * 
 * @see UploadException
 * @copyright 2020-2022 Gilles VANDERSTRAETEN gillesvds@adok.info
 */
class Upload
{
	/**
	 * Chemin du fichier temporaire côté serveur.
	 */
	public ?string $tmpFilePath = null;

	/**
	 * Vrai si fichier présent et correctement uploadé.
	 */
	public bool $complete = false;

	/**
	 * Vérifie l'upload.
	 * Déclenche une UploadException en cas d'erreur.
	 *
	 * @param string $inputName Nom du champ INPUT de type 'file'.
	 * @param int $maxFileSize Taille maxi du fichier (octets).
	 * @param array $allowedMimeTypes Tableau des types MIME autorisés.
	 * @param boolean $optional True si le fichier est facultatif, false sinon.
	 * @throws UploadException Si erreur.
	 */
	public function __construct(string $inputName, int $maxFileSize, array $allowedMimeTypes = [], bool $optional = true)
	{
		// Récupérer le fichier.
		$file = $_FILES[$inputName] ?? null;
		// Il y a toujours une entrée dans $_FILES sauf si la taille du fichier excède la limite 'post_max_size'.
		// Dans ce cas et dans les autres cas de dépassement, déclencher une exception.
		if (!$file || $file['error'] === UPLOAD_ERR_INI_SIZE || $file['error'] === UPLOAD_ERR_FORM_SIZE)
			throw new UploadException(UploadException::FILE_SIZE_EXCEEDS_CLIENT_OR_SERVER_LIMIT);
		// Récupérer le chemin serveur temporaire.
		$this->tmpFilePath = $file['tmp_name'];
		// Si le fichier est absent...
		if ($file['error'] === UPLOAD_ERR_NO_FILE) {
			// Si le fichier est facultatif, retourner.
			if ($optional)
				return;
			// Sinon, déclencher une exception.
			throw new UploadException(UploadException::FILE_NOT_FOUND);
		}
		// Si le fichier n'est pas un fichier uploadé, déclencher une exception généraliste.
		if (!is_uploaded_file($this->tmpFilePath))
			throw new UploadException(UploadException::UPLOAD_FAILED);
		// Si le fichier est vide, déclencher une exception.
		if (!$file['size'])
			throw new UploadException(UploadException::EMPTY_FILE);
		// Si la taille du fichier dépasse la limite fixée, déclencher une exception.
		if ($file['size'] > $maxFileSize)
			throw new UploadException(UploadException::FILE_SIZE_EXCEEDS_APPLICATION_LIMIT);
		// Si types MIME imposés et fichier non compatible, déclencher une exception.
		if ($allowedMimeTypes && !in_array($file['type'], $allowedMimeTypes))
			throw new UploadException(UploadException::WRONG_MIME_TYPE);
		// Si autre erreur, déclencher une exception généraliste.
		if ($file['error'] !== UPLOAD_ERR_OK)
			throw new UploadException(UploadException::UPLOAD_FAILED);
		// Marquer l'upload comme complet.
		$this->complete = true;
	}

	/**
	 * Sauvegarde le fichier uploadé selon le chemin donné.
	 * Déclenche une UploadException en cas d'erreur.
	 *
	 * @param string $path Chemin complet du fichier cible.
	 * @throws UploadException Si erreur.
	 */
	public function save(string $path): void
	{
		// Utiliser move_uploaded_file() comme recommandé.
		if (@!move_uploaded_file($this->tmpFilePath, $path)) // @ pour éviter le warning.
			throw new UploadException(UploadException::FILE_COPY_FAILED);
	}
}

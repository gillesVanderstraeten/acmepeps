<?php

declare(strict_types=1);

namespace peps\upload;

use Exception;

/**
 * Exceptions en lien avec Upload.
 * Classe 100% statique.
 * 
 * @see Exception
 * @see Upload
 * @copyright 2020-2022 Gilles VANDERSTRAETEN gillesvds@adok.info
 */
class UploadException extends Exception
{
	// Messages d'erreur.
	public const FILE_SIZE_EXCEEDS_CLIENT_OR_SERVER_LIMIT = "Le poids du fichier excède la limite côté client ou serveur.";
	public const FILE_NOT_FOUND = "Le fichier est introuvable.";
	public const UPLOAD_FAILED = "L'upload a échoué.";
	public const EMPTY_FILE = "Le fichier est vide.";
	public const FILE_SIZE_EXCEEDS_APPLICATION_LIMIT = "Le poids du fichier excède la limite fixée par l'application.";
	public const WRONG_MIME_TYPE = "Le type MIME du fichier est invalide.";
	public const FILE_COPY_FAILED = "L'enregistrement du fichier a échoué.";
}

"use strict";

// Copyright (c) 2020-2022 Gilles VANDERSTRAETEN gillesvds@adok.info

/*
* Retenir la source initiale pour la restaurer si le fichier choisi est invalide.
*/
// Récupérer une référence à l'élément IMG.
let img = document.querySelector('#thumbnail img');
// Retenir la source initiale.
let initialSource = img.src;

/*
* Charger la photo choisie par drag and drop le cas échéant.
*/
// Supprimer le comportement par défaut de dragover.
img.addEventListener('dragover', evt => evt.preventDefault());
// Supprimer le comportement par défaut de drop et transférer les données.
img.addEventListener('drop', evt => {
	evt.preventDefault();
	// Transférer les données à l'élément INPUT.
	document.form1.photo.files = evt.dataTransfer.files;
	// Déléguer la vérification et l'affichage à displayPhoto().
	displayPhoto(evt.dataTransfer.files);
});

/**
 * Récupérer et vérifier la photo choisie.
 * Si valide, afficher la photo.
 * Retourner systématiquement true pour un comportement correct si le fichier a été amené par drag and drop.
 * 
 * @param {FileList} files 
 * @returns True systématiquement.
 */
function displayPhoto(files) {
	// return true; // DEBUG: pour tester les sécurités côté serveur.
	// Si pas de FileList ou FileList vide, abandonner.
	if (!files || !files.length)
		return true;
	// Récupérer le File en premier élément de la FileList.
	let file = files[0];
	// Si le fichier est vide, abandonner.
	if (!file.size) {
		return abortDisplay("Le fichier est vide.");
	}
	// Si le fichier est trop lourd, abandonner.
	if (file.size > IMG_MAX_FILE_SIZE) {
		return abortDisplay("Le poids du fichier excède la limite fixée par l'application.");
	}
	// Si le type MIME du fichier est invalide, abandonner.
	if (IMG_ALLOWED_MIME_TYPES.length && !IMG_ALLOWED_MIME_TYPES.includes(file.type)) {
		return abortDisplay("Le type MIME du fichier est invalide.");
	}
	// Instancier un FileReader.
	let reader = new FileReader();
	// Définir le traitement à effectuer quand le résultat de la lecture sera disponible.
	reader.onload = () => img.src = reader.result;
	// Lire le fichier.
	reader.readAsDataURL(file);
	// Retourner true.
	return true;
}

/**
 * Abandonner l'affichage de la photo choisie.
 * Retourner systématiquement true pour un comportement correct si le fichier a été amené par drag and drop.
 * 
 * @param {string} error 
 * @returns True systématiquement.
 */
function abortDisplay(error) {
	// Alerter.
	alert(error);
	// Supprimer la vignette de l'affichage.
	document.form1.photo.value = '';
	// Rétablir la source initiale de l'image.
	img.src = initialSource;
	// Retourner true.
	return true;
}

"use strict";

// Copyright (c) 2020-2022 Gilles VANDERSTRAETEN gillesvds@adok.info

/**
 * Supprimer un produit et ses images.
 * 
 * @param {number} idProduct PK du produit.
 * @returns void
 */
function deleteAll(idProduct) {
	// Si l'utilisateur le confirme, rediriger vers la route adéquate.
	if (confirm("Vraiment supprimer le produit et ses photos ?")) {
		location = `/product/delete/${idProduct}/all`;
	}
}

/**
 * Supprimer les images d'un produit.
 * 
 * @param {number} idProduct PK du produit.
 * @returns void
 */
function deleteImg(idProduct) {
	// Si l'utilisateur le confirme, rediriger vers la route adéquate.
	if (confirm("Vraiment supprimer les photos du produit ?")) {
		location = `/product/delete/${idProduct}/img`;
	}
}

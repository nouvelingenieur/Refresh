<?php
/*
	Plateforme web PPR - outil de crowdwourcing
	Copyright(C) 2011 Nicolas SEICHEPINE

	This file is part of PPR.

	PPR is free software: you can redistribute it and/or modify
    it under the terms of the GNU Affero General Public License as
    published by the Free Software Foundation, either version 3 of the
    License, or (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU Affero General Public License for more details.

    You should have received a copy of the GNU Affero General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.

	Contact : contact_ppr@seichepine.org
*/


// ** Réglages MySQL - Votre hébergeur doit vous fournir ces informations. ** //
/** Nom de la base de données de WordPress. */
define('DB_NAME', 'nom_de_la_base');

/** Utilisateur de la base de données MySQL. */
define('DB_USER', 'nom_utilisateur');

/** Mot de passe de la base de données MySQL. */
define('DB_PASSWORD', 'mot_de_passe');

/** Adresse de l'hébergement MySQL. */
define('DB_HOST', 'serveur');

/**
 * Préfixe de base de données pour les tables de Refresh.
 *
 * Vous pouvez installer plusieurs Refresh sur une seule base de données
 * si vous leur donnez chacune un préfixe unique.
 * N'utilisez que des chiffres, des lettres non-accentuées, et des caractères soulignés!
 */
$table_prefix  = 'refresh_';

?>

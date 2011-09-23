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

/** Define ABSPATH as this files directory */
define( 'ABSPATH', dirname(__FILE__) . '/' );

////////////////////////////////////////////////////////////////////////////////
// ** Réglages MySQL - Votre hébergeur doit vous fournir ces informations. ** //
////////////////////////////////////////////////////////////////////////////////
/** Nom de la base de données de WordPress. */
define('DB_NAME', 'nom_de_la_base');

/** Utilisateur de la base de données MySQL. */
define('DB_USER', 'nom_utilisateur');

/** Mot de passe de la base de données MySQL. */
define('DB_PASSWORD', 'mot_de_passe');

/** Adresse de l'hébergement MySQL. */
define('DB_HOST', 'serveur');

///////////////////////////////////////////////////
// ** Paramètres de customisation de l'école. ** //
///////////////////////////////////////////////////
/** Nom de l'école. */
define('NOM_ECOLE', 'Education');

/** Adresse mail du contact en charge de modérer la plateforme. */
define('MAIL_CONTACT', 'contact@nouvelingenieur.fr');

/** Mots clés caractérisant le Refresh. */
define('KEYWORDS', 'Education, Refresh, Innovation, Nouvel Ingénieur, Nouveaux Etudiants');

///////////////////////////////////
// ** Paramètres de sécurité. ** //
///////////////////////////////////
/** Limitations à un domaine pour les adresses mail. */
define('LIMIT_MAIL', false); // boolean
/** Indique les adresses mail à autoriser en expression régulière. */
define('PREGMATCH_MAIL', '#^([a-z][\-_]?)*[a-z]+\.([a-z][\-_]?)*[a-z]+@eleves\.ecole\.fr$#'); // regular expression 
/** Indique les adresses mail à autoriser en langage compréhensible. */
define('PREGMATCH_MAIL_HUMAN_READABLE', 'de type prenom.nom@eleve.ecole.fr'); // regular expression 


?>

<?php

/*
	Plateforme web PPR - outil de crowdsourcing
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
	
	Contact : nicolas.seichepine.org/?action=contact
*/
session_start();

include_once("config.php");
include_once("script_php/pages_secondlevel/actions.php");

$privileges = user_privilege_level();
$login = $_SESSION['login_c'];


// Demande
if (isset($_GET["action"]) && is_string($_GET["action"]))
{
	$ccar_to_treat=htmlentities($_GET["action"]);
}

// Appel de la fonction associée à la demande
switch ($ccar_to_treat)
{
	// appel de la fonction post par Ajax
	case "post":
		post($_POST['title'],$_POST['message'],$_POST['anonymization'],$_POST['category'],$login,$valid=0,$output='JSON');
		break;
		
	case "comments":
		get_comments($_POST['thread_id'],$privileges,$login,$output='JSON');
		break;

	// Message d'erreur
	default:
		// unexistent_page();
		break;
}

?>
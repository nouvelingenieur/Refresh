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

include_once("pages_secondlevel/tool.php");

if (is_logged())
{
	echo('
	
	<a href="?action=logout">'.$WORDS['DISCONNECT'].'</a>
	<a href="?action=change_pass">'.$WORDS['CHANGEPWD'].'</a>
	<a href="?action=delete_account">'.$WORDS['UNSUBSCRIBE'].'</a>
	<a href="?action=display_useterms">'.$WORDS['USERCOND'].'</a>

	');
	
	if (user_privilege_level()>3) // Repr�sentant CER/administrateur
	{
		echo('
		
		<a href="?action=new_document">Ajouter un document</a>
		
		');
	}
}
else
{
	echo('
	
	<a href="?action=login">Connexion</a>
	<a href="?action=lost_ids">Identifiants perdus</a>
	<a href="?action=create_account">Inscription</a>
	<a href="?action=display_useterms">Conditions d\'utilisation</a>

	');
}

?>
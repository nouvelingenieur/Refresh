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
	
	<a href="?action=logout">'._('Disconnect').'</a>
	<a href="?action=change_pass">'._('Change password').'</a>
	<a href="?action=delete_account">'._('Unsubscribe').'</a>
	<a href="?action=display_useterms">'._('Terms of use').'</a>

	');
	
	if (user_privilege_level()>3) // Représentant CER/administrateur
	{
		echo('
		
		<a href="?action=new_document">Ajouter un document</a>
		
		');
	}
}
else
{
	echo('
	
	<a href="?action=login">'._('Connexion').'</a>
	<a href="?action=lost_ids">'._('Lost your log?').'</a>
	<a href="?action=create_account">'._('Register').'</a>
	<a href="?action=display_useterms">'._('Terms of use').'</a>

	');
}

?>

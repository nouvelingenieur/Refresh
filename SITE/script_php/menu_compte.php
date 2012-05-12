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

						
	$email = "$_SESSION[login_c]";
	$defaultavatar = "img/default-avatar.png";
	$size = 80;
	$grav_url = "http://www.gravatar.com/avatar/" . md5( strtolower( trim( $email ) ) ) . "?d=" . urlencode( $defaultavatar ) . "&s=" . $size;
	echo '<img src="'. $grav_url .'" alt="avatar" />';

	echo('
	
	<li><a href="?action=logout">'._('Disconnect').'</a></li>
	<li><a href="?action=change_pass">'._('Change password').'</a></li>
	<li><a href="?action=delete_account">'._('Unsubscribe').'</a></li>

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
	log_in();
	echo('
	<li><a href="?action=lost_ids">'._('Lost your log?').'</a></li>
	<li><a href="?action=create_account">'._('Register').'</a></li>
	<li><a href="?action=display_useterms">'._('Terms of use').'</a></li>

	');
}

?>

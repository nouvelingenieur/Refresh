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

function unexistent_page()
{
	echo('<h1>La page demand&eacute;e n\'existe pas</h1>');
}

function need_enpc_member_privilege($level=1)
{
	if ($level==2)
	{
		echo('

		<h1>Vous n\'avez pas le droit d\'acc&eacute;der &agrave; cette page</h1>
		<h2><a href="?action=login">Identifiez-vous</a>, visitez ce site depuis le r&eacute;seau &eacute;tudiant de l\'ENPC ou utilisez un 
		<a href="http://eleves.enpc.fr/rsf/accesrsf.htm">VPN</a></h2>

		');
	}
	else
	{
		echo('

		<h1>Vous n\'avez pas le droit d\'acc&eacute;der &agrave; cette page</h1>
		<h2><a href="?action=login">Identifiez-vous</a>, visitez ce site depuis l\'&Eacute;cole ou utilisez un 
		<a href="http://eleves.enpc.fr/rsf/accesrsf.htm">VPN</a></h2>

		');
	}
}

function need_logged_member_privilege()
{
	echo('

	<h1>Vous n\'avez pas le droit d\'acc&eacute;der &agrave; cette page</h1>
	<h2>Il est n&eacute;cessaire de vous <a href="?action=login">identifier</a></h2>

	');
}

?>

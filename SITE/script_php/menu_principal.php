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

$highlighted_title=1;	
				
if (isset($_GET["action"]) && is_string($_GET["action"]))
{
	$ccar_to_treat=htmlentities($_GET["action"]);
	if (empty($ccar_to_treat) || $ccar_to_treat=="go_home")
	{
		$highlighted_title=1;
	}
	elseif ($ccar_to_treat=="display_nouvelingenieur")
	{
		$highlighted_title=2;
	}
	elseif ($ccar_to_treat=="display_docu")
	{
		$highlighted_title=3;
	}
	elseif ($ccar_to_treat=="display_post")
	{
		$highlighted_title=4;
	}
	elseif ($ccar_to_treat=="new_post")
	{
		$highlighted_title=5;
	}
	else
	{
		$highlighted_title=6;
	}
}

switch ($highlighted_title)
{
	case 1:
		echo('
			<td class="menu_title_selected_first">
				<a href="?action=go_home">Accueil</a>
			</td>
			<td class="menu_title">
				<a href="?action=display_nouvelingenieur">Le Nouvel Ing&eacute;nieur</a>
			</td>
			<td class="menu_title">
				<a href="?action=display_docu">Documents</a>
			</td>
			<td class="menu_title">
				<a href="?action=display_post">Propositions</a>
			</td>
			<td class="menu_title">
				<a href="?action=new_post">Participer</a>
			</td>
		');
		break;
	case 2:
		echo('
			<td class="menu_title_first">
				<a href="?action=go_home">Accueil</a>
			</td>
			<td class="menu_title_selected">
				<a href="?action=display_nouvelingenieur">Le Nouvel Ing&eacute;nieur</a>
			</td>
			<td class="menu_title">
				<a href="?action=display_docu">Documents</a>
			</td>
			<td class="menu_title">
				<a href="?action=display_post">Propositions</a>
			</td>
			<td class="menu_title">
				<a href="?action=new_post">Participer</a>
			</td>
		');
		break;
	case 3:
		echo('
			<td class="menu_title_first">
				<a href="?action=go_home">Accueil</a>
			</td>
			<td class="menu_title">
				<a href="?action=display_nouvelingenieur">Le Nouvel Ing&eacute;nieur</a>
			</td>
			<td class="menu_title_selected">
				<a href="?action=display_docu">Documents</a>
			</td>
			<td class="menu_title">
				<a href="?action=display_post">Propositions</a>
			</td>
			<td class="menu_title">
				<a href="?action=new_post">Participer</a>
			</td>
		');
		break;
	case 4:
		echo('
			<td class="menu_title_first">
				<a href="?action=go_home">Accueil</a>
			</td>
			<td class="menu_title">
				<a href="?action=display_nouvelingenieur">Le Nouvel Ing&eacute;nieur</a>
			</td>
			<td class="menu_title">
				<a href="?action=display_docu">Documents</a>
			</td>
			<td class="menu_title_selected">
				<a href="?action=display_post">Propositions</a>
			</td>
			<td class="menu_title">
				<a href="?action=new_post">Participer</a>
			</td>
		');
		break;
	case 5:
		echo('
			<td class="menu_title_first">
				<a href="?action=go_home">Accueil</a>
			</td>
			<td class="menu_title">
				<a href="?action=display_nouvelingenieur">Le Nouvel Ing&eacute;nieur</a>
			</td>
			<td class="menu_title">
				<a href="?action=display_docu">Documents</a>
			</td>
			<td class="menu_title">
				<a href="?action=display_post">Propositions</a>
			</td>
			<td class="menu_title_selected">
				<a href="?action=new_post">Participer</a>
			</td>
		');
		break;
	case 6:
		echo('
			<td class="menu_title_first">
				<a href="?action=go_home">Accueil</a>
			</td>
			<td class="menu_title">
				<a href="?action=display_nouvelingenieur">Le Nouvel Ing&eacute;nieur</a>
			</td>
			<td class="menu_title">
				<a href="?action=display_docu">Documents</a>
			</td>
			<td class="menu_title">
				<a href="?action=display_post">Propositions</a>
			</td>
			<td class="menu_title">
				<a href="?action=new_post">Participer</a>
			</td>
		');
		break;
}

?>


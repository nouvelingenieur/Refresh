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

$link=@mysql_connect(DB_HOST,DB_USER,DB_PASSWORD);

if ($link)
{
	if (!(@mysql_select_db(DB_NAME, $link)))
	{
		echo('<div class="warning">Probl&egrave;me de connexion &agrave; la base, la navigation risque d\'&ecirc;tre perturb&eacute;e</div>');
	}
}
else
{
	echo('<div class="warning">Probl&egrave;me de connexion &agrave; la base, la navigation risque d\'&ecirc;tre perturb&eacute;e</div>');	
}

?>
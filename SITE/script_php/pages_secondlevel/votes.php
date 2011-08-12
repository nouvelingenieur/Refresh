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

include_once("tool.php");
include_once("errors.php");

function get_votes_from_thread($thread_id)
{
	$retour=array("pro_votes"=>0,"against_votes"=>0,"choice"=>0);
	$rights=user_privilege_level();

	if ($rights>1)
	{
		if ($rights>2) // L'utilisateur est loggé et a droit de vote, on doit vérifier s'il a déjà voté et si oui dans quel sens
		{
			// Vaut-il mieux faire porter la charge sur le serveur SQL en utilisant deux requêtes dont une vérifiant les hashs, ou sur ENPC.org en effectuant les vérifications dans la boucle PHP ?
			$result=@mysql_query(sprintf("SELECT vote_id,rand_prop,hash_prop,vote FROM vote WHERE thread_id='%s'",mysql_real_escape_string($thread_id)));
			if ($result)
			{
				while($row=mysql_fetch_assoc($result))
				{
					if ($row["vote"]==1)
					{
						$retour["pro_votes"]++;
						if (check_property($row["rand_prop"],$row["hash_prop"]))
						{
							$retour["choice"]=$row["vote_id"]; // On note un vote pour et l'ID du vote
						}
					}
					elseif ($row["vote"]==0)
					{
						$retour["against_votes"]++;
						if (check_property($row["rand_prop"],$row["hash_prop"]))
						{
							$retour["choice"]=-1*($row["vote_id"]); // On note un vote contre et l'ID du vote
						}
					}
				}
				@mysql_free_result($result);
			}
			else // Erreur lors de la requête
			{
				$retour["pro_votes"]=-1;
				$retour["against_votes"]=-1;
			}
		}
		else // On peut se contenter du décompte
		{
			$result=@mysql_query(sprintf("SELECT SUM(vote) AS pro_vote, count(vote) AS total_vote FROM vote WHERE thread_id='%s'",mysql_real_escape_string($thread_id)));
			if ($result && $row=mysql_fetch_assoc($result))
			{
				if (!isset($row["pro_vote"])) // Cas vides
				{
					$row["pro_vote"]=0;
				}
				if (!isset($row["total_vote"]))
				{
					$row["total_vote"]=0;
				}
				
				$retour["pro_votes"]=$row["pro_vote"];
				$retour["against_votes"]=$row["total_vote"]-$row["pro_vote"];
				@mysql_free_result($result);
			}
			else
			{
				$retour["pro_votes"]=-1;
				$retour["against_votes"]=-1;
			}
		}
	}
	else // La fonction ne renvoie rien à un utilisateur ne disposant pas des droits suffisants
	{
		$retour["pro_votes"]=-1;
		$retour["against_votes"]=-1;
	}

	return $retour;
}

?>
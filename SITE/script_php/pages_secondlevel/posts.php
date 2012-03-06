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

include_once("actions.php");

// conversion des variables POST en variables SESSION
function modify_thread_display_filtering()
{
	if (isset($_POST['form_name']) && $_POST['form_name']=="thread_display_param")
	{
		if(isset($_POST["admin_recherche"]))
		{
			$category_choice=$_POST["admin_recherche"];
			$_SESSION["thread_admin_recherche"]=$category_choice;
		}
		if(isset($_POST["category_filter"]))
		{
			$category_choice=$_POST["category_filter"];
			if (is_numeric($category_choice))
			{
				$_SESSION["thread_category_filter"]=$category_choice;
			}
		}
		if(isset($_POST["admin_filter"]))
		{
			$category_choice=$_POST["admin_filter"];
			if (is_numeric($category_choice))
			{
				$_SESSION["thread_admin_filter"]=$category_choice;
			}
			
			if ($category_choice>3 && user_privilege_level()<4) // N'afficherait de toute façon rien, mais un utilisateur normal n'a pas à se mettre en mode modération
			{
				unset($_SESSION["thread_admin_filter"]);
			}
			
			if (!is_logged())
			{
				unset($_SESSION["thread_admin_filter"]); // Pas de filtrage utilisateur si non loggé
			}
		}
		
		if(isset($_POST["sorting"]))
		{
			$display_order=$_POST["sorting"];
			if (is_numeric($display_order))
			{
				$_SESSION["thread_ordering"]=$display_order;
			}
		}
		unset($_SESSION["thread_page"]); // Lorsque le filtrage change, le nombre de requête n'est plus valide ; l'affichage doit donc repartir de la première page
	}
	unset($_POST); // Par précaution, la page est de toute façon rechargée juste après
}

function modify_thread_display_page()
{
	if (isset($_GET["num_page"]))
	{
		$new_page=$_GET["num_page"];
		if (is_numeric($new_page) && $new_page>0)
		{
			$_SESSION["thread_page"]=$new_page;
		}
	}
}

function moderate_post() // Attention, générique, s'applique et aux posts et aux commentaires
{
	$thread_id=-1;
	$comment_id=-1;
	$decision=-1;

	// Récupération des arguments
	if (isset($_GET["thread_id"]))
	{
		$thread_id=$_GET["thread_id"];
	}
	if (isset($_GET["comment_id"]))
	{
		$comment_id=$_GET["comment_id"];
	}
	if (isset($_GET["order"]))
	{
		$decision=$_GET["order"];
	}
	
    if (user_privilege_level()>3) // Droits d'administrateur nécessaires à la modération
    {
		if (($thread_id*$comment_id)>0) // Aucun objet désignés, ou deux types d'objet désignés à la fois
		{
			$_SESSION['transient_display']='<div class="warning">Impossible de d&eacute;terminer l\'objet auquel appliquer la commande de mod&eacute;ration</div>';
		}
		else
		{
			$query="";
			if ($thread_id>0) // L'ordre s'applique à un post
			{
				if ($decision==1)
	            {
	                $query=sprintf("UPDATE thread SET is_valid=1, already_mod=1, chaine_moderation=''  WHERE thread_id='%s'",mysql_real_escape_string($thread_id));
	            }
	            elseif ($decision==0)
	            {
	                $query=sprintf("UPDATE thread SET is_valid=0, already_mod=1, chaine_moderation=''  WHERE thread_id='%s'",mysql_real_escape_string($thread_id));
	            }
			}
			else // L'ordre s'applique à un commentaire
			{
				if ($decision==1)
	            {
	                $query=sprintf("UPDATE comment SET is_valid=1, already_mod=1, chaine_moderation=''  WHERE comment_id='%s'",mysql_real_escape_string($comment_id));
	            }
	            elseif ($decision==0)
	            {
	                $query=sprintf("UPDATE comment SET is_valid=0, already_mod=1, chaine_moderation=''  WHERE comment_id='%s'",mysql_real_escape_string($comment_id));
	            }
			}
			
			if (empty($query)) // Décision ni à 1 ni à 0
            {
                $_SESSION['transient_display']='<div class="warning">D&eacute;cision non valide</div>';
            }
            else
            {
                if (@mysql_query($query)) // Exécution de la commande
                {   
                    $_SESSION['transient_display']='<div class="success">Commande de mod&eacute;ration effectu&eacute;e</div>';
					if ($comment_id>0) // On a modéré un commentaire, il faut mettre à jour les dates pour le thread associé
					{
						$query_p1=@mysql_query(sprintf("SELECT thread_id FROM comment WHERE comment_id='%s'",mysql_real_escape_string($comment_id)));
						if ($query_p1 && $res_p1=mysql_fetch_assoc($query_p1))
						{
							$clean_tid=mysql_real_escape_string($res_p1["thread_id"]);
							@mysql_query(sprintf("UPDATE thread SET datecom=(SELECT IF(ISNULL(MAX( date )),'0000-00-00',MAX( date )) FROM comment WHERE thread_id='%s' AND is_valid=1) WHERE thread_id='%s'",$clean_tid,$clean_tid));
						}
					}
				}
                else
                {
                    $_SESSION['transient_display']='<div class="warning">Erreur durant la commande de mod&eacute;ration</div>';
                }
			}
		}
    }
    else
    {
        $_SESSION['transient_display']='<div class="warning">Vous ne disposez pas des droits pour mod&eacute;rer</div>';
    }
}

function moderate_mail() // Volontairement : pas besoin de droits spécifiques (mais d'avoir reçu le mail avec la chaîne de confirmation)
{
	echo('<h1>Système de modération par mail :</h1>');
	$success=false;
	if (isset($_GET['type']) && isset($_GET['id']) && isset($_GET['cconf']))
	{
		$request="";
		$marqu_comm=false;
		switch($_GET['type'])
		{
			case 'comment':
				$request=sprintf("UPDATE comment SET is_valid=1, already_mod=1, chaine_moderation='' WHERE comment_id='%s' AND chaine_moderation='%s'",mysql_real_escape_string($_GET['id']),sha1($_GET['cconf']));
				$marqu_comm=true;				
				break;
			case 'proposition':
				$request=sprintf("UPDATE thread SET is_valid=1, already_mod=1, chaine_moderation='' WHERE thread_id='%s' AND chaine_moderation='%s'",mysql_real_escape_string($_GET['id']),sha1($_GET['cconf']));
				break;
		}
		if (!empty($request))
		{
			@mysql_query($request);
			if (mysql_affected_rows()>0)
			{
				$success=true;
				if ($marqu_comm)
				{
					$query_p1=@mysql_query(sprintf("SELECT thread_id FROM comment WHERE comment_id='%s'",mysql_real_escape_string($_GET['id'])));
					if ($query_p1 && $res_p1=mysql_fetch_assoc($query_p1))
					{
						$clean_tid=mysql_real_escape_string($res_p1["thread_id"]);
						@mysql_query(sprintf("UPDATE thread SET datecom=(SELECT IF(ISNULL(MAX( date )),'0000-00-00',MAX( date )) FROM comment WHERE thread_id='%s' AND is_valid=1) WHERE thread_id='%s'",$clean_tid,$clean_tid));
					}
				}
			}	
		}
	}
	if ($success)
		echo('<div class="success">Commande de mod&eacute;ration effectu&eacute;e</div>');
	else
		echo('<div class="warning">Erreur durant la commande de mod&eacute;ration - lien invalide</div>');
}


function change_post_confidentiality_status() // Attention, générique, s'applique et aux posts et aux commentaires
{
	$thread_id=-1;
	$comment_id=-1;
	$choice=-1;

	// Récupération des arguments
	if (isset($_GET["thread_id"]))
	{
		$thread_id=$_GET["thread_id"];
	}
	if (isset($_GET["comment_id"]))
	{
		$comment_id=$_GET["comment_id"];
	}
	if (isset($_GET["order"]))
	{
		$choice=$_GET["order"];
	}
	
	if (user_privilege_level()>2) // Le demandeur doit-être loggé et posséder des droits d'écriture
    {
		if (($thread_id*$comment_id)>0) // Aucun objet désigné, ou deux types d'objet désignés à la fois
		{
			$_SESSION['transient_display']='<div class="warning">Impossible de d&eacute;terminer l\'objet auquel appliquer la commande de mod&eacute;ration</div>';
		}
		else
		{
			$result="";
			
			// Vérification de l'appartenance de l'ID au demandeur (pourraît être intégré à la requête SQL si nécessaire)
			if ($thread_id>0) // L'ordre s'applique à un post
			{
				$result=@mysql_query(sprintf("SELECT rand_prop,hash_prop FROM thread WHERE thread_id='%s'",mysql_real_escape_string($thread_id)));
			}
			else // L'ordre s'applique à un commentaire
			{
				$result=@mysql_query(sprintf("SELECT rand_prop,hash_prop FROM comment WHERE comment_id='%s'",mysql_real_escape_string($comment_id)));
			}
			
			// L'ID existe bien
			if ($result && $row=mysql_fetch_assoc($result))
			{
				// On vérifie l'appartenance au demandeur
				if(check_property($row["rand_prop"],$row["hash_prop"]))
				{
					$query="";
					// Commande de mise à jour
					if ($thread_id>0) // L'ordre s'applique à un post
					{
						if ($choice==1)
						{
							$query=sprintf("UPDATE thread SET possibly_name='%s' WHERE thread_id='%s'",mysql_real_escape_string(construct_name_from_session()),mysql_real_escape_string($thread_id));
						}
						elseif ($choice==0)
						{
							$query=sprintf("UPDATE thread SET possibly_name='%s' WHERE thread_id='%s'","",mysql_real_escape_string($thread_id));
						}
					}
					else // L'ordre s'applique à un commentaire
					{
						$query="";
						if ($choice==1)
						{
							$query=sprintf("UPDATE comment SET possibly_name='%s' WHERE comment_id='%s'",mysql_real_escape_string(construct_name_from_session()),mysql_real_escape_string($comment_id));
						}
						elseif ($choice==0)
						{
							$query=sprintf("UPDATE comment SET possibly_name='%s' WHERE comment_id='%s'","",mysql_real_escape_string($comment_id));
						}
					}
					
					// La décision n'était ni 0 ni 1
					if (empty($query))
					{
						$_SESSION['transient_display']='<div class="warning">D&eacute;cision non valide</div>';
					}
					else
					{
						// On exécute la commande et note le résultat
						if (@mysql_query($query))
						{   
							$_SESSION['transient_display']='<div class="success">Propri&eacute;t&eacute;s de confidentialit&eacute; correctement mises &agrave; jour</div>';
						}
						else
						{
							$_SESSION['transient_display']='<div class="warning">Erreur durant la mise &agrave; jour des propri&eacute;t&eacute;s de confidentialit&eacute;</div>';
						}
					}	
				}
				else
				{
					$_SESSION['transient_display']='<div class="warning">Vous devez &ecirc;tre le propri&eacute;taire du message pour effectuer cette op&eacute;ration</div>';
				}
				@mysql_free_result($result);
			}
			else
			{
				$_SESSION['transient_display']='<div class="warning">Id de l\'objet invalide</div>';
			}
		}
	}
	else
	{
		$_SESSION['transient_display']='<div class="warning">Vous ne disposez pas des droits suffisants</div>';
	}
}

function vote_post()
{
	$thread_id=-1;
	$choice="";
	
	if (isset($_GET["thread_id"]))
	{
		$thread_id=$_GET["thread_id"];
	}
	if (isset($_GET["order"]))
	{
		$choice=$_GET["order"];
	}

	if (user_privilege_level()>2) // Il faut être loggé et posséder des droits d'écriture
    {
		if (!($choice==-1 || $choice==0 || $choice==1))
		{
			$_SESSION['transient_display']='<div class="warning">Demande de vote incorrecte</div>';
		}
		elseif ($thread_id>0)
		{
			// Sélection d'un éventuel vote dont on serait propriétaire pour ce post
			$result=@mysql_query(sprintf("SELECT vote_id, vote FROM vote WHERE thread_id='%s' AND CAST(SHA1(CONCAT('%s',CAST(rand_prop AS CHAR))) AS CHAR)=hash_prop",mysql_real_escape_string($thread_id),mysql_real_escape_string($_SESSION['login_c'])));
			if ($result)
			{
				$vote_prec=0; // On part du principe qu'on n'a pas voté au préalable
				$id_vote=-1; // L'id est mis à jour si un vote est retrouvé
				if ($row=mysql_fetch_assoc($result))
				{
					$id_vote=$row["vote_id"];
					if($row["vote"]==1) // On a voté pour au préalable
					{
						$vote_prec=1;
					}
					elseif($row["vote"]==0) // On a voté contre au préalable
					{
						$vote_prec=-1;
					}
				}

				if($choice==-1)
				{
					if($vote_prec==-1) // On a déjà voté contre
					{
						$_SESSION['transient_display']='<div class="warning">Vote d&eacute;j&agrave; enregistr&eacute;</div>';
					}
					elseif($vote_prec==0) // On souhaite voter pour la première fois contre
					{
						$rand_prop=mt_rand(0,65535);
						$hash_prop=sha1($_SESSION['login_c'].$rand_prop);
						$thrad_id_sec=mysql_real_escape_string($thread_id);
						if (@mysql_query("INSERT INTO `vote` (`vote_id`,`thread_id`,`rand_prop`,`hash_prop`,`vote`) VALUES (NULL, '$thrad_id_sec','$rand_prop','$hash_prop','0')"))
						{
							$_SESSION['transient_display']='<div class="success">Vote correctement pris en compte</div>';
						}
						else
						{
							$_SESSION['transient_display']='<div class="warning">Erreur lors de l\'insertion du vote</div>';
						}
					}
					elseif($vote_prec==1) // On souhaite passer d'un vote pour à un vote contre
					{
						if (@mysql_query(sprintf("UPDATE vote SET vote=0 WHERE vote_id='%s'",mysql_real_escape_string($id_vote))))
						{
							$_SESSION['transient_display']='<div class="success">Vote correctement mis &agrave; jour</div>';
						}
						else
						{
							$_SESSION['transient_display']='<div class="warning">Erreur lors de la mise &agrave; jour du vote</div>';
						}
					}
				}
				elseif($choice==0)
				{
					if($vote_prec==-1 || $vote_prec==1) // On souhaite annuler un vote
					{
						if(@mysql_query(sprintf("DELETE FROM vote WHERE vote_id='%s'",mysql_real_escape_string($id_vote))))
						{
							$_SESSION['transient_display']='<div class="success">Vote correctement annul&eacute;</div>';
						}
						else
						{
							$_SESSION['transient_display']='<div class="warning">Erreur lors de l\'annulation du vote</div>';
						}
					}
					elseif($vote_prec==0) // On souhaite annuler un vote... qui n'existe pas
					{
						$_SESSION['transient_display']='<div class="warning">Erreur lors de l\'annulation du vote</div>';
					}
				}
				elseif($choice==1)
				{
					if($vote_prec==-1) // On souhaite passer d'un vote contre à un vote pour
					{
						if (@mysql_query(sprintf("UPDATE vote SET vote=1 WHERE vote_id='%s'",mysql_real_escape_string($id_vote))))
						{
							$_SESSION['transient_display']='<div class="success">Vote correctement mis &agrave; jour</div>';
						}
						else
						{
							$_SESSION['transient_display']='<div class="warning">Erreur lors de la mise &agrave; jour du vote</div>';
						}
					}
					elseif($vote_prec==0) // On souhaite voter pour la première fois pour
					{
						$rand_prop=mt_rand(0,65535);
						$hash_prop=sha1($_SESSION['login_c'].$rand_prop);
						$thrad_id_sec=mysql_real_escape_string($thread_id);
						if (@mysql_query("INSERT INTO `vote` (`vote_id`,`thread_id`,`rand_prop`,`hash_prop`,`vote`) VALUES (NULL, '$thrad_id_sec','$rand_prop','$hash_prop','1')"))
						{
							$_SESSION['transient_display']='<div class="success">Vote correctement pris en compte</div>';
						}
						else
						{
							$_SESSION['transient_display']='<div class="warning">Erreur lors de l\'insertion du vote</div>';
						}
					}
					elseif($vote_prec==1) // On a déjà voté pour
					{
						$_SESSION['transient_display']='<div class="warning">Vote d&eacute;j&agrave; enregistr&eacute;</div>';
					}
				}
				@mysql_free_result($result);
			}
			else // Mieux vaux ne pas continuer si l'on n'a pas pu vérifier ce qui existait en base
			{
				$_SESSION['transient_display']='<div class="warning">Erreur lors de la requ&ecirc;te</div>';
			}
		}
		else
		{
			$_SESSION['transient_display']='<div class="warning">Id de la proposition non valide</div>';
		}
	}
	else
	{
		$_SESSION['transient_display']='<div class="warning">Vous ne disposez pas des droits suffisants</div>';
	}
}

function new_post()
{
	if (user_privilege_level()>2)
	{
		echo('<h1>Publication d\'une nouvelle proposition :</h1>');

		// Valeurs réintroduites dans le formulaire en cas d'erreur
		$affich_form=true;
        $title_prec="";
        $text_prec="";
        $anon_prec="";
		$cate_prec=0;

		if (isset($_SESSION['post']))
		{
			$_POST=$_SESSION['post'];
			unset($_SESSION['post']);
		}

		// Le formulaire a été validé
		if (isset($_POST['form_name']) && $_POST['form_name']=="create_thread")
		{
			$action = post(trim($_POST["title"]),trim($_POST["message"]),$_POST["anonymization"],$_POST["category"],$_SESSION['login_c']);
			$action->echo_warnings();
			$action->echo_successes();
			if($action->result) {
				$affich_form=false;
			}
		}
			
		if ($affich_form) // Affichage du formulaire en incluant d'éventuelles valeurs
		{
			echo('
			<div class="enlarge_lowresol">
            <form method="post" action="?action=new_post">
				<table class="tab_form">
					<tr>
						<td>
							Titre :
						</td>
						<td>
							<input type="text" name="title" value="'.htmlentities($title_prec).'" />
						</td>
					</tr>
					<tr>
						<td>
							Cat&eacute;gorie :
						</td>
                        <td>
							<select name="category">');

			$tail="";
	        $result=@mysql_query("SELECT category_id,category_name FROM thread_category");
	        if ($result)
			{
	            while($row=mysql_fetch_assoc($result))
	            {
					if ($row["category_id"]==$cate_prec)
					{
						$tail.='<option value="'.htmlentities($row["category_id"]).'" selected="selected">'.htmlentities($row["category_name"]).'</option>';
					}
					else
					{
						$tail.='<option value="'.htmlentities($row["category_id"]).'">'.htmlentities($row["category_name"]).'</option>';
					}
	            }
				@mysql_free_result($result);
	        }
			if (empty($tail))
			{
				$tail='<option value="0">Defaut</option>';
			}

	        echo($tail.'
							</select>
						</td>
					</tr>
					<tr>
						<td>
							Proposition :
						</td>
						<td>
                            <textarea name="message" rows="10" cols="50">'.htmlentities($text_prec).'</textarea>
						</td>
					</tr>					
					<tr>
						<td>
							Anonymiser :
						</td>
						<td>');

            if (empty($anon_prec))
            {
                echo('<input type="checkbox" name="anonymization" />');
            }   
            else
            {
				echo('<input type="checkbox" name="anonymization" checked="checked" />');
            }

            echo('
						</td>
					</tr>
					<tr>
						<td>
							<input type="hidden" name="form_name" value="create_thread" />
						</td>
						<td></td>
					</tr>
					<tr class="submit_center">
						<td colspan="2" rowspan="1">
							<input type="submit" value="Valider" />
						</td>
					</tr>
				</table>
			</form>
			</div>
			');
		}

        if (isset($_POST))
        {
            unset($_POST);
        }

        echo('

        <br /><br />
        <p>
			<span class="footnote">
            <b>Note :</b> L\'anonymat repose sur un m&eacute;canisme utilisant une valeur al&eacute;atoirement attribu&eacute;e &agrave; chaque 
			proposition. En pratique, vous pourrez donc &agrave; tout moment &eacute;diter votre message, le supprimer, l\'anonymiser ou 
			au contraire faire afficher votre nom. Mais dans le cas o&ugrave; vous activez l\'anonymisation, strictement personne, administrateurs compris, 
			ne sera capable de vous associer &agrave; un message donn&eacute; &agrave; partir des seules informations stock&eacute;es par le site.
			</span>
        </p>

        ');
	}
	else
	{
		need_logged_member_privilege();
	}
}

function display_post()
{
	$privileges=user_privilege_level();
	if ($privileges>1)
	{		
		// Titre et messages éventuels

		
		if(isset($_SESSION['transient_display']))
		{
			echo($_SESSION['transient_display']);
			unset($_SESSION['transient_display']);
		}
		
		// ******************************************************************* //
		// Formulaire pour gérer le filtrage/l'ordonnancement des propositions //
		// ******************************************************************* //
		
		
		// Menu de sélection des idées
		$tail='<div class="enlarge_lowresol">
			<form method="post" action="?action=post_filter_change">
				<table class="tab_form_close">
					<tr>';
					
		// Champ de recherche
		$tail.='<td>
			Recherche :
		</td>
		<td>
			<input type="text" name="admin_recherche" value="'.$_SESSION["thread_admin_recherche"].'">
		</td>';
		
		// Choix de catégorie
		$tail.='<td>
							Cat&eacute;gorie :
						</td>
						<td>
							<select name="category_filter">
								<option value="0">Toutes</option>';
					
		$result=@mysql_query("SELECT category_id,category_name FROM thread_category"); // Menu déroulant de choix de catégorie en fonction de ce qui est disponible en base
		if ($result)
		{
			while($row=mysql_fetch_assoc($result))
			{
				if (isset($_SESSION["thread_category_filter"]) && $row["category_id"]==$_SESSION["thread_category_filter"])
				{
					$tail.='<option value="'.htmlentities($row["category_id"]).'" selected="selected">'.htmlentities($row["category_name"]).'</option>';
				}
				else
				{
					$tail.='<option value="'.htmlentities($row["category_id"]).'">'.htmlentities($row["category_name"]).'</option>';
				}
			}
			@mysql_free_result($result);
		}
		$tail.='</select></td>';
		
		// Menu de filtrage pour les utilisateurs loggés
		if(is_logged())
		{
			
			$tail.='<td>
				Filtre :
			</td>
			<td>
				<select name="admin_filter">
					<option value="0">Aucun</option>';
			
			if ($privileges>3) // Proposition des options de modération
			{
				if (isset($_SESSION["thread_admin_filter"]))
				{
					switch($_SESSION["thread_admin_filter"])
					{
						case 1:
							$tail.='<option value="1" selected="selected">Mes propositions</option><option value="2">Propositions avec mes commentaires</option><option value="3">Propositions sans mon vote</option> 
								<option value="4">Propositions non mod&eacute;r&eacute;es</option><option value="5">Commentaires non mod&eacute;r&eacute;s</option>';
							break;
						case 2:
							$tail.='<option value="1">Mes propositions</option><option value="2" selected="selected">Propositions avec mes commentaires</option><option value="3">Propositions sans mon vote</option> 
								<option value="4">Propositions non mod&eacute;r&eacute;es</option><option value="5">Commentaires non mod&eacute;r&eacute;s</option>';
							break;
						case 3:
							$tail.='<option value="1">Mes propositions</option><option value="2">Propositions avec mes commentaires</option><option value="3" selected="selected">Propositions sans mon vote</option> 
								<option value="4">Propositions non mod&eacute;r&eacute;es</option><option value="5">Commentaires non mod&eacute;r&eacute;s</option>';
							break;
						case 4:
							$tail.='<option value="1">Mes propositions</option><option value="2">Propositions avec mes commentaires</option><option value="3">Propositions sans mon vote</option> 
								<option value="4" selected="selected">Propositions non mod&eacute;r&eacute;es</option><option value="5">Commentaires non mod&eacute;r&eacute;s</option>';
							break;
						case 5:
							$tail.='<option value="1">Mes propositions</option><option value="2">Propositions avec mes commentaires</option><option value="3">Propositions sans mon vote</option> 
								<option value="4">Propositions non mod&eacute;r&eacute;es</option><option value="5" selected="selected">Commentaires non mod&eacute;r&eacute;s</option>';
							break;
						default:
							$tail.='<option value="1">Mes propositions</option><option value="2">Propositions avec mes commentaires</option><option value="3">Propositions sans mon vote</option> 
								<option value="4">Propositions non mod&eacute;r&eacute;es</option><option value="5">Commentaires non mod&eacute;r&eacute;s</option>';
					}
				}
				else
				{
					$tail.='<option value="1">Mes propositions</option><option value="2">Propositions avec mes commentaires</option><option value="3">Propositions sans mon vote</option> 
						<option value="4">Propositions non mod&eacute;r&eacute;es</option><option value="5">Commentaires non mod&eacute;r&eacute;s</option>';
				}
			}
			else // Menu simple
			{
				if (isset($_SESSION["thread_admin_filter"]))
				{
					switch($_SESSION["thread_admin_filter"])
					{
						case 1:
							$tail.='<option value="1" selected="selected">Mes propositions</option><option value="2">Propositions avec mes commentaires</option><option value="3">Propositions sans mon vote</option>';
							break;
						case 2:
							$tail.='<option value="1">Mes propositions</option><option value="2" selected="selected">Propositions avec mes commentaires</option><option value="3">Propositions sans mon vote</option>';
							break;
						case 3:
							$tail.='<option value="1">Mes propositions</option><option value="2">Propositions avec mes commentaires</option><option value="3" selected="selected">Propositions sans mon vote</option>';
							break;
						default:
							$tail.='<option value="1">Mes propositions</option><option value="2">Propositions avec mes commentaires</option><option value="3">Propositions sans mon vote</option>';
					}
				}
				else
				{
					$tail.='<option value="1">Mes propositions</option><option value="2">Propositions avec mes commentaires</option><option value="3">Propositions sans mon vote</option>';
				}
			}
			$tail.='</select></td>';
		}

		// Menu pour le tri des résultats
		if (isset($_SESSION["thread_ordering"])) // Menu déroulant de choix de l'ordre d'affichage
		{
			if (isset($_SESSION["thread_admin_filter"]) && $_SESSION["thread_admin_filter"]==5)  // Cas de la modération des commentaires à part
			{
				$tail.='<td>
							Trier par :
						</td>
						<td>
							<select name="sorting" disabled="disabled">
								<option value="1" selected="selected">Date</option><option value="2">Nombre de votes favorables</option><option value="3">Proportion de votes favorables</option><option value="4">Nombre total de votes</option>"';
			}
			else
			{
				$tail.='<td>
							Trier par :
						</td>
						<td>
							<select name="sorting">';
				switch($_SESSION["thread_ordering"])
				{
					case 1:
						$tail.="<option value=\"1\" selected=\"selected\">Date</option><option value=\"2\">Nombre de votes favorables</option><option value=\"3\">Proportion de votes favorables</option><option value=\"4\">Nombre total de votes</option>";
						break;
					case 2:
						$tail.="<option value=\"1\">Date</option><option value=\"2\" selected=\"selected\">Nombre de votes favorables</option><option value=\"3\">Proportion de votes favorables</option><option value=\"4\">Nombre total de votes</option>";
						break;
					case 3:
						$tail.="<option value=\"1\">Date</option><option value=\"2\">Nombre de votes favorables</option><option value=\"3\" selected=\"selected\">Proportion de votes favorables</option><option value=\"4\">Nombre total de votes</option>";
						break;
					case 4:
						$tail.="<option value=\"1\">Date</option><option value=\"2\">Nombre de votes favorables</option><option value=\"3\">Proportion de votes favorables</option><option value=\"4\" selected=\"selected\">Nombre total de votes</option>";
						break;
					default:
						$tail.="<option value=\"1\">Date</option><option value=\"2\">Nombre de votes favorables</option><option value=\"3\">Proportion de votes favorables</option><option value=\"4\">Nombre total de votes</option>";
				}
			}			
		}
		else
		{
			if (isset($_SESSION["thread_admin_filter"]) && $_SESSION["thread_admin_filter"]==4)  // Cas de la modération des commentaires à part
			{
				$tail.='<td>
							Trier par :
						</td>
						<td>
							<select name="sorting" disabled="disabled">
								<option value="1">Date</option><option value="2">Nombre de votes favorables</option><option value="3">Proportion de votes favorables</option><option value="4">Nombre total de votes</option>"';
			}
			else
			{
				$tail.='<td>
							Trier par :
						</td>
						<td>
							<select name="sorting">
								<option value="1">Date</option><option value="2">Nombre de votes favorables</option><option value="3">Proportion de votes favorables</option><option value="4">Nombre total de votes</option>';
			}
		}

		echo($tail.'			
						</select>
					</td>
					<td>
						<input type="hidden" name="form_name" value="thread_display_param" />
					</td>
					<td>
						<input type="submit" value="Valider" />
					</td>
				</tr>
			</table>
		</form></div>');	

		$is_admin=($privileges>3);
		
		// ****************************************************************************** //
		// Affichage des résultats selon les paramètres définis dans les menus précédents //
		// ****************************************************************************** //
		
		if (isset($_SESSION["thread_admin_filter"]) && $_SESSION["thread_admin_filter"]==5) // Mode modération des commentaires
		{
			affichage_comments(-1,true); // Affichage "brutal" des commentaires confié à une autre fonction
		}
		else
		{
			$current_mod=(isset($_SESSION["thread_admin_filter"]) && $_SESSION["thread_admin_filter"]==4); // Mode modération
			$vote_filt=(isset($_SESSION["thread_admin_filter"]) && $_SESSION["thread_admin_filter"]==3); // Filtrage selon les votes

			// ************************************************************ //
			// Construction de la requête de rappatriement des propositions //
			// ************************************************************ //
			
			$recherche = "(T.text LIKE '%".mysql_real_escape_string($_SESSION['thread_admin_recherche'])."%' OR T.title LIKE '%".mysql_real_escape_string($_SESSION['thread_admin_recherche'])."%') AND";
			
			// Requête de base (deux parties pour prendre en comptes les propositions sans votes)
			$query_p1="(SELECT T.thread_id, T.rand_prop, T.hash_prop, T.title, T.text, T.date, T.is_valid, T.possibly_name, T.already_mod, G.category_name,
					SUM(V.vote) AS pro_vote, COUNT(V.vote) AS total_vote
					FROM thread T, thread_category G, vote V
					WHERE ".$recherche." V.thread_id=T.thread_id AND G.category_id=T.category";
			$query_p2="(SELECT T.thread_id, T.rand_prop, T.hash_prop, T.title, T.text, T.date, T.is_valid, T.possibly_name, T.already_mod, G.category_name,
					0 AS pro_vote, 0 AS total_vote
					FROM thread T, thread_category G 
					WHERE ".$recherche." T.thread_id <> ALL (SELECT thread_id FROM vote) AND G.category_id=T.category";
			$query_count="SELECT COUNT(T.thread_id) AS NUM_RES FROM thread T, thread_category G WHERE G.category_id=T.category"; // Requête à part pour déterminer préalablement le nombre de résultats
			
			
			if (isset($_SESSION["thread_admin_filter"])) // Contraintes possibles pour les utilisateurs loggés
			{
				switch($_SESSION["thread_admin_filter"])
				{
					case 0: // Aucune contrainte si ce n'est une question de droits d'affichage
						if (is_logged())
						{
							if(!$is_admin)
							{
								$query_p1.=sprintf(" AND (T.is_valid=1 OR (CAST(SHA1(CONCAT('%s',CAST(T.rand_prop AS CHAR))) AS CHAR)=T.hash_prop))",mysql_real_escape_string($_SESSION['login_c']));
								$query_p2.=sprintf(" AND (T.is_valid=1 OR (CAST(SHA1(CONCAT('%s',CAST(T.rand_prop AS CHAR))) AS CHAR)=T.hash_prop))",mysql_real_escape_string($_SESSION['login_c']));
								$query_count.=sprintf(" AND (T.is_valid=1 OR (CAST(SHA1(CONCAT('%s',CAST(T.rand_prop AS CHAR))))=T.hash_prop))",mysql_real_escape_string($_SESSION['login_c']));
							}
						}
						else // Une personne non loggée n'aurait pas du pouvoir obtenir le fait que $_SESSION["thread_admin_filter"] soit défini
						{
							$query_p1.=" AND FALSE";
							$query_p2.=" AND FALSE";
							$query_count.=" AND FALSE";
						}
						break;
					case 1: // Posts propriétaires
						if (is_logged())
						{
							$query_p1.=sprintf(" AND (CAST(SHA1(CONCAT('%s',CAST(T.rand_prop AS CHAR))) AS CHAR)=T.hash_prop)",mysql_real_escape_string($_SESSION['login_c']));
							$query_p2.=sprintf(" AND (CAST(SHA1(CONCAT('%s',CAST(T.rand_prop AS CHAR))) AS CHAR)=T.hash_prop)",mysql_real_escape_string($_SESSION['login_c']));
							$query_count.=sprintf(" AND (CAST(SHA1(CONCAT('%s',CAST(T.rand_prop AS CHAR))))=T.hash_prop)",mysql_real_escape_string($_SESSION['login_c']));
						}
						else // Utilisateur non loggé, on ne peut pas trouver ses propositions (et il ne lui est normalement pas possible d'obtenir $_SESSION["thread_admin_filter"]==1)
						{
							$query_p1.=" AND FALSE";
							$query_p2.=" AND FALSE";
							$query_count.=" AND FALSE";
						}
						break;
					case 2: // Posts commentés
						if (is_logged())
						{
							$query_p1.=sprintf(" AND T.thread_id IN (SELECT DISTINCT thread_id FROM comment WHERE (CAST(SHA1(CONCAT('%s',CAST(rand_prop AS CHAR))) AS CHAR)=hash_prop))",mysql_real_escape_string($_SESSION['login_c']));
							$query_p2.=sprintf(" AND T.thread_id IN (SELECT DISTINCT thread_id FROM comment WHERE (CAST(SHA1(CONCAT('%s',CAST(rand_prop AS CHAR))) AS CHAR)=hash_prop))",mysql_real_escape_string($_SESSION['login_c']));
							$query_count.=sprintf(" AND T.thread_id IN (SELECT DISTINCT thread_id FROM comment WHERE (CAST(SHA1(CONCAT('%s',CAST(rand_prop AS CHAR))) AS CHAR)=hash_prop))",mysql_real_escape_string($_SESSION['login_c']));
							if(!$is_admin) // Toujours la contrainte sur les droits d'affichage
							{
								$query_p1.=sprintf(" AND (T.is_valid=1 OR (CAST(SHA1(CONCAT('%s',CAST(T.rand_prop AS CHAR))) AS CHAR)=T.hash_prop))",mysql_real_escape_string($_SESSION['login_c']));
								$query_p2.=sprintf(" AND (T.is_valid=1 OR (CAST(SHA1(CONCAT('%s',CAST(T.rand_prop AS CHAR))) AS CHAR)=T.hash_prop))",mysql_real_escape_string($_SESSION['login_c']));
								$query_count.=sprintf(" AND (T.is_valid=1 OR (CAST(SHA1(CONCAT('%s',CAST(T.rand_prop AS CHAR))))=T.hash_prop))",mysql_real_escape_string($_SESSION['login_c']));
							}
						}
						else // Utilisateur non loggé, on ne peut pas trouver ses propositions (et il ne lui est normalement pas possible d'obtenir $_SESSION["thread_admin_filter"]==2)
						{
							$query_p1.=" AND FALSE";
							$query_p2.=" AND FALSE";
							$query_count.=" AND FALSE";
						}
						break;
					case 3: // Posts pour lesquels je n'ai pas voté	
						if (is_logged())
						{
							$query_p1.=sprintf(" AND T.thread_id NOT IN (SELECT DISTINCT thread_id FROM vote WHERE (CAST(SHA1(CONCAT('%s',CAST(rand_prop AS CHAR))) AS CHAR)=hash_prop))",mysql_real_escape_string($_SESSION['login_c']));
							$query_p2.=sprintf(" AND T.thread_id NOT IN (SELECT DISTINCT thread_id FROM vote WHERE (CAST(SHA1(CONCAT('%s',CAST(rand_prop AS CHAR))) AS CHAR)=hash_prop))",mysql_real_escape_string($_SESSION['login_c']));
							$query_count.=sprintf(" AND T.thread_id NOT IN (SELECT DISTINCT thread_id FROM vote WHERE (CAST(SHA1(CONCAT('%s',CAST(rand_prop AS CHAR))) AS CHAR)=hash_prop))",mysql_real_escape_string($_SESSION['login_c']));
							if(!$is_admin) // Toujours la contrainte sur les droits d'affichage
							{
								$query_p1.=sprintf(" AND (T.is_valid=1 OR (CAST(SHA1(CONCAT('%s',CAST(T.rand_prop AS CHAR))) AS CHAR)=T.hash_prop))",mysql_real_escape_string($_SESSION['login_c']));
								$query_p2.=sprintf(" AND (T.is_valid=1 OR (CAST(SHA1(CONCAT('%s',CAST(T.rand_prop AS CHAR))) AS CHAR)=T.hash_prop))",mysql_real_escape_string($_SESSION['login_c']));
								$query_count.=sprintf(" AND (T.is_valid=1 OR (CAST(SHA1(CONCAT('%s',CAST(T.rand_prop AS CHAR))))=T.hash_prop))",mysql_real_escape_string($_SESSION['login_c']));
							}
						}
						else // Utilisateur non loggé, on ne peut pas trouver ses propositions (et il ne lui est normalement pas possible d'obtenir $_SESSION["thread_admin_filter"]==2)
						{
							$query_p1.=" AND FALSE";
							$query_p2.=" AND FALSE";
							$query_count.=" AND FALSE";
						}
						break;						
					case 4: // Propositions non modérées
						if($is_admin)
						{
							$query_p1.=" AND T.already_mod=0";
							$query_p2.=" AND T.already_mod=0";
							$query_count.=" AND T.already_mod=0";
						}
						else // Utilisateur non administrateur, ne devrait pas pouvoir passer en mode modération, dans le doute on n'affiche rien
						{
							$query_p1.=" AND FALSE";
							$query_p2.=" AND FALSE";
							$query_count.=" AND FALSE";
						}
						break;
					default: // Dans le doute
						$query_p1.=" AND FALSE";
						$query_p2.=" AND FALSE";
						$query_count.=" AND FALSE";
				}
			}
			else
			{
				if (!$is_admin) // Limitation de la recherche selon les droits du demandeur
				{
					if (is_logged())
					{
						$query_p1.=sprintf(" AND (T.is_valid=1 OR (CAST(SHA1(CONCAT('%s',CAST(T.rand_prop AS CHAR))) AS CHAR)=T.hash_prop))",mysql_real_escape_string($_SESSION['login_c']));
						$query_p2.=sprintf(" AND (T.is_valid=1 OR (CAST(SHA1(CONCAT('%s',CAST(T.rand_prop AS CHAR))) AS CHAR)=T.hash_prop))",mysql_real_escape_string($_SESSION['login_c']));
						$query_count.=sprintf(" AND (T.is_valid=1 OR (CAST(SHA1(CONCAT('%s',CAST(T.rand_prop AS CHAR))))=T.hash_prop))",mysql_real_escape_string($_SESSION['login_c']));
					}
					else
					{
						$query_p1.=" AND T.is_valid=1";
						$query_p2.=" AND T.is_valid=1";
						$query_count.=" AND T.is_valid=1";
					}
				}
			}
			
			// Filtrage éventuel selon la catégorie
			if (isset($_SESSION["thread_category_filter"]) && $_SESSION["thread_category_filter"]>0)
			{
				$category_searched=mysql_real_escape_string($_SESSION["thread_category_filter"]);
				$query_p1.=" AND T.category=$category_searched";
				$query_p2.=" AND T.category=$category_searched";
				$query_count.=" AND T.category=$category_searched";
			}
			
			// Regroupement propositions avec/sans votes
			$query=$query_p1.' GROUP BY T.thread_id, T.rand_prop, T.hash_prop, T.title, T.text, T.date, T.is_valid, T.possibly_name, G.category_name) UNION '.$query_p2.')';
			
			
			// Détermination du nombre résultats potentiellement retournés, pour connaître la répartition par pages
			$num_res=-1; // Valeur par défaut en cas d'échec
			$res=@mysql_query($query_count);
			if ($res)
			{
				if($num_res=mysql_fetch_assoc($res))
				{
					$num_res=$num_res["NUM_RES"];
				}
				@mysql_free_result($res);
			}
			
			// Suite de la construction de la requête, GROUP et ORDER BY
			if (isset($_SESSION["thread_ordering"]))
			{
				switch($_SESSION["thread_ordering"])
				{
					case 2:
						$query.=" ORDER BY pro_vote DESC";
						break;
					case 3:
						$query.=" ORDER BY pro_vote/total_vote DESC";
						break;
					case 4:
						$query.=" ORDER BY total_vote DESC";
						break;
					default:
						$query.=" ORDER BY date DESC";
						break;
				}
			}
			else
			{
				$query.=" ORDER BY date DESC";
			}
			
			// Fin de la construction de la requête, LIMIT selon la page affichée
			$page_to_display=1;
			if (!isset($_SESSION["thread_page"])) // Affichage de la première page par défaut, correction des arguments
			{
				$_SESSION["thread_page"]=1;
			}
			else
			{
				$page_to_display=$_SESSION["thread_page"];
				if (!is_numeric($page_to_display) || $page_to_display<1)
				{
					$_SESSION["thread_page"]=1;
					$page_to_display=1;
				}
			}
			
			if ($num_res>-1) // On a été capable de vérifier combien de résultats étaient disponibles ; on ne limite pas la requête sinon
			{
				$offset=round(10*($page_to_display-1));
				if ($offset>=$num_res) // On a dépassé, sans doute par erreur, on retourne à la page 1
				{
					$offset=0;
					$_SESSION["thread_page"]=1;
				}
				$query.=" LIMIT $offset,10";
			}
			
			// ********************************************************************* //
			// Menu de changement de page, sauvegardé pour affichage en base de page //
			// ********************************************************************* //
			
			$change_page="";
			if ($num_res>10)
			{
				$change_page.='<div class="bottom_page_choice">';
				for ($i=1;$i<ceil($num_res/10)+1;$i++)
				{
					if($i==$_SESSION["thread_page"])
					{
						$change_page.="$i&nbsp;&nbsp;";
					}
					else
					{
						$change_page.='<a href="?action=change_thread_page&amp;num_page='.$i.'">'.$i.'</a>&nbsp;&nbsp;';
					}
				}
				$change_page.='</div><br/>';
				echo($change_page);
			}
			else
			{
				echo('<br />');
			}
			
			////////////////////////////////////////////////////////
			// Exécution de la requête et affichage des résultats //
			////////////////////////////////////////////////////////

			$result=@mysql_query($query);
			if ($result)
			{
				$result_returned=false;
				$need_separator=false;
				while($row=mysql_fetch_assoc($result))
				{
					// Informations diverses sur le post
					$result_returned=true;
					$thread_id=$row["thread_id"];
					$thread_id_affiche=htmlentities($thread_id);
					
					$is_proprio=check_property($row["rand_prop"],$row["hash_prop"]);
					$is_valid=$row["is_valid"];
					$already_mod=$row["already_mod"];

					$check_vote=get_votes_from_thread($thread_id);
					$pro_vote=$check_vote["pro_votes"];
					$agt_vote=$check_vote["against_votes"];
					$per_vote=$check_vote["choice"];

					// Hormis l'auteur ou un administrateur, affichage uniquement si le post a été modéré
					if ($is_valid || $is_proprio || $privileges>3)
					{
					if($need_separator)
						{
							echo('<hr />');
						}
						$need_separator=true;
						
						echo '
						<article class="feed_item ym-grid v-grid linearize-level-1 id="item-'.$thread_id_affiche.'"">
						<div class="ym-g25 ym-gl">
						<div class="ym-gbox-left">
						';
						
						// Etat de modération
						if ($privileges>3)
						{
							if ($already_mod)
							{
								if ($is_valid)
								{
									echo('<img src="img/modere.png" alt="Mod&eacute;r&eacute;" class="imgtitlenews" />');
								}
								else
								{
									echo('<img src="img/masque.png" alt="Masqu&eacute;" class="imgtitlenews" />');
								}
							}
							else
							{
								echo('<img src="img/n_modere.png" alt="Non mod&eacute;r&eacute;" class="imgtitlenews" />');
							}
						}
						elseif ($is_proprio)
						{
							if ($already_mod)
							{
								if (!$is_valid)
								{
									echo('<img src="img/masque.png" alt="Masqu&eacute;" class="imgtitlenews" />');
								}
							}
							else
							{
								echo('<img src="img/n_modere.png" alt="Non mod&eacute;r&eacute;" class="imgtitlenews" />');
							}
						}
						
						
						// Catégories avec images
						echo('
						<div class="ym-gbox">
						<img src="img/placeholder_100x100.gif" alt="icon" class="avatar bordered"/>
						</div>
						');
						
						// Votes
						if ($privileges>2) // L'utilisateur peut voter, liens de vote, lien d'annulation le cas échéant
						{
							echo('<span class="vote">');
							if ($per_vote>0)
							{
								echo('<a href="?action=vote_post&amp;order=0&amp;thread_id='.$thread_id_affiche.'#'.$thread_id_affiche.'"><img src="img/bright_votepro.png" alt="+1" class="imgvote" /></a>');
							}
							else
							{
								if ($vote_filt) // Le fait de voter chage le contenu de la page affiché, on n'utilise donc pas d'ancre
								{
									echo('<a href="?action=vote_post&amp;order=1&amp;thread_id='.$thread_id_affiche.'"><img src="img/pale_votepro.png" alt="+1" class="imgvote" /></a>');
								}
								else
								{
									echo('<a href="?action=vote_post&amp;order=1&amp;thread_id='.$thread_id_affiche.'#'.$thread_id_affiche.'"><img src="img/pale_votepro.png" alt="+1" class="imgvote" /></a>');
								}
							}
							
							if ($per_vote<0)
							{
								echo('<a href="?action=vote_post&amp;order=0&amp;thread_id='.$thread_id_affiche.'#'.$thread_id_affiche.'"><img src="img/bright_voteneg.png" alt="-1" class="imgvote" /></a>');
							}
							else
							{
								if ($vote_filt) // Le fait de voter chage le contenu de la page affiché, on n'utilise donc pas d'ancre
								{
									echo('<a href="?action=vote_post&amp;order=-1&amp;thread_id='.$thread_id_affiche.'"><img src="img/pale_voteneg.png" alt="-1" class="imgvote" /></a>');
								}
								else
								{
									echo('<a href="?action=vote_post&amp;order=-1&amp;thread_id='.$thread_id_affiche.'#'.$thread_id_affiche.'"><img src="img/pale_voteneg.png" alt="-1" class="imgvote" /></a>');
								}
							}
							echo('</span>');
							
						}
						
						//close-open columns
						echo ('
						  </div>
						    </div>
						    
						    
						    <div class="ym-g75 ym-gr">
						     <div class="ym-gbox-right ym-clearfix">');
						     
						//add gravatar
						echo ('
						       <header class="ym-clearfix">
							 <div class="ym-g20 ym-gl">
							  <div class="ym-gbox">
							     <img src="img/placeholder_50x50.gif" alt="icon" class="avatar bordered"/>
							  </div>
							 </div>
						');
						
						//start meta 
						echo ('
							 <div class="ym-g80 ym-gr">
							  <div class="ym-gbox">
							  <p class="meta">
							   <small>
							   Posted by :
						');
						
						// name + link to profile
						if (!empty($row["possibly_name"]))
						{
							echo('<a href="#">'.htmlentities($row["possibly_name"]).'</a>');
						}
						
						// tags
						echo ('
							with tags : <a href="#">'.htmlentities($row["category_name"]).'</a>'
						);
						
						//close meta
						echo('
						</small>
						  </p>');
						
						// Titre
						echo('
						<h3>'.htmlentities(stripslashes($row["title"])).'</h3>');
						
						//share sns buttons + close header
						echo('
						  <section class="sns"><!-- AddThis Button BEGIN -->
							<div class="addthis_toolbox addthis_default_style ">
							<a class="addthis_button_preferred_1"></a>
							<a class="addthis_button_preferred_2"></a>
							<a class="addthis_button_preferred_3"></a>
							</div>
							<script type="text/javascript" src="http://s7.addthis.com/js/250/addthis_widget.js#pubid=xa-4f3e39a4223675c7"></script>
						  </section><!-- AddThis Button END -->
						 </div><!--gbox -->
						</div><!--ymg-80 -->
					       </header>
					       ');
						
						
						/*
						echo('
						<div class="votebar">');
						
						// Etat des votes
						if ($pro_vote==0)
						{
							if($agt_vote==0)
							{
								echo('<span class="neutralvote">
										<span class="votebarannotation">
											+0
										</span>
									</span>
								</div>');
							}
							else
							{
								echo('<span class="againstvote" style="height:100%;width:8px;">
										<span class="votebarannotation">
											-'.htmlentities($agt_vote).'
										</span>
									</span>
								</div>');
							}
						}
						else
						{
							if($agt_vote==0)
							{
								echo('<span class="provote" style="height:100%;width:8px;">
										<span class="votebarannotation_pro">
											+'.htmlentities($pro_vote).'
										</span>
									</span>
								</div>');
							}
							else
							{
								$prop_pro=round(100*$pro_vote/($agt_vote+$pro_vote));
								echo('<span class="provote" style="height:'.$prop_pro.'%;width:8px;">
										<span class="votebarannotation_pro">
											+'.htmlentities($pro_vote).'
										</span>
									</span>
									<span class="againstvote" style="height:'.(100-$prop_pro).'%;width:8px;">
										<span class="votebarannotation">
											-'.htmlentities($agt_vote).'
										</span>
									</span>
								</div>');
							}
						}
						*/
						
						// Corps du texte
						echo('<div class="content"><p>'.text_display_prepare($row["text"]).'</p></div>');
						// utils 
						echo('<footer class="utils">
							<p>
							<small>
							');
							

						//upvote
						echo ('<a href="?action=vote_post&amp;order='. '1' . '&amp;thread_id=' . $thread_id_affiche. '">'._('Upvote'). '</a>'
						);
						
						echo (" - ");
						
						// downvote
						echo ('<a href="?action=vote_post&amp;order='. '-1' . '&amp;thread_id=' . $thread_id_affiche. '">'._('Downvote'). '</a>'
						);
						
						echo (" - ");
						
						// comments
						affichage_comments($thread_id,false);
						
						echo (" - ");
						
						// Date
						echo('<time datetime="'.htmlentities(transfo_date($row["date"])).'">' . htmlentities(transfo_date($row["date"])) . '</time>');
						
						echo (" | ");
							
						if ($is_proprio || $privileges>4) // Administrateurs et propriétaires peuvent éditer et supprimer
						{
							echo('
								<a href="?action=edit_post&amp;thread_id='.$thread_id_affiche.'">'._('Edit').'</a>
								 - 
								<a href="?action=remove_post&amp;thread_id='.$thread_id_affiche.'">'._('Delete').'</a>');
							if ($is_proprio)
							{
								if (!empty($row["possibly_name"]))
								{
									echo(' - <a href="?action=anonymization&amp;order=0&amp;thread_id='.$thread_id_affiche.'#'.$thread_id_affiche.'">'._('Hide my name').'</a>');
								}
								else
								{
									echo(' - <a href="?action=anonymization&amp;order=1&amp;thread_id='.$thread_id_affiche.'#'.$thread_id_affiche.'">'._('Hide my name').'</a>');
								}
							}
						}

						if ($is_admin) // Administrateurs et modérateurs peuvent afficher ou masquer le post
						{					
							if($is_valid || !$already_mod)
							{
								if($current_mod) // La modération retire le message de la liste affichée, on repart en tête de liste
								{
									echo('<a href="?action=moderation&amp;order=0&amp;thread_id='.$thread_id_affiche.'">Refuser</a>');
								}
								else // On reste à la même hauteur dans la page
								{
									echo('<a href="?action=moderation&amp;order=0&amp;thread_id='.$thread_id_affiche.'#'.$thread_id_affiche.'">Refuser</a>');
								}
							}
							if(!$is_valid || !$already_mod)
							{
								if($current_mod) // La modération retire le message de la liste affichée, on repart en tête de liste
								{
									echo('<a href="?action=moderation&amp;order=1&amp;thread_id='.$thread_id_affiche.'">Accepter</a>');
								}
								else // On reste à la même hauteur dans la page
								{
									echo('<a href="?action=moderation&amp;order=1&amp;thread_id='.$thread_id_affiche.'#'.$thread_id_affiche.'">Accepter</a>');
								}
							}
						}

					echo '</small></footer></article>';
					}
				}
				
				// Affichage vide / d'un cadre de choix de page / d'un avertissement sur le nombre de résultats / selon les cas
				if ($result_returned)
				{
					echo($change_page.'<div class="newsterminator"><hr />'.NOM_ECOLE.' REFRESH</div>');
				}
				else
				{
					echo('<div class="warning">Aucune proposition n\'est disponible selon les critères choisis</div>');
				}
				@mysql_free_result($result);
			}
			else
			{
				echo('<div class="warning">Erreur lors de la requ&ecirc;te</div>');
			}
		}		
	}
	else
	{
		need_ecole_member_privilege(2);
	}
}

?>

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
include_once("votes.php");

function modify_thread_display_filtering()
{
	if (isset($_POST['form_name']) && $_POST['form_name']=="thread_display_param")
	{
		if(isset($_POST["category_filter"]))
		{
			$category_choice=$_POST["category_filter"];
			if (is_numeric($category_choice))
			{
				$_SESSION["thread_category_filter"]=$category_choice;
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
		unset($_SESSION["thread_page"]); // Lorsque le filtrage change, le nombre de requ�te n'est plus valide ; la page affich�e doit �tre �cras�e en cons�quence
	}
	unset($_POST); // Par pr�caution, la page est de toute fa�on recharg�e juste apr�s
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

function moderate_post() // Attention, g�n�rique, s'applique et aux posts et aux commentaires
{
	$thread_id=-1;
	$comment_id=-1;
	$decision=-1;

	// R�cup�ration des arguments
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
	
    if (user_privilege_level()>3) // Droits d'administrateur n�cessaires � la mod�ration
    {
		if (($thread_id*$comment_id)>0) // Aucun objet d�sign�s, ou deux types d'objet d�sign�s � la fois
		{
			$_SESSION['transient_display']='<div class="warning">Impossible de d&eacute;terminer l\'objet auquel appliquer la commande de mod&eacute;ration</div>';
		}
		else
		{
			$query="";
			if ($thread_id>0) // L'ordre s'applique � un post
			{
				if ($decision==1)
	            {
	                $query=sprintf("UPDATE thread SET is_valid=1, already_mod=1 WHERE thread_id='%s'",mysql_real_escape_string($thread_id));
	            }
	            elseif ($decision==0)
	            {
	                $query=sprintf("UPDATE thread SET is_valid=0, already_mod=1 WHERE thread_id='%s'",mysql_real_escape_string($thread_id));
	            }
			}
			else // L'ordre s'applique � un commentaire
			{
				if ($decision==1)
	            {
	                $query=sprintf("UPDATE comment SET is_valid=1, already_mod=1 WHERE comment_id='%s'",mysql_real_escape_string($comment_id));
	            }
	            elseif ($decision==0)
	            {
	                $query=sprintf("UPDATE comment SET is_valid=0, already_mod=1 WHERE comment_id='%s'",mysql_real_escape_string($comment_id));
	            }
			}
			
			if (empty($query)) // D�cision ni � 1 ni � 0
            {
                $_SESSION['transient_display']='<div class="warning">D&eacute;cision non valide</div>';
            }
            else
            {
                if (@mysql_query($query)) // Ex�cution de la commande
                {   
                    $_SESSION['transient_display']='<div class="success">Commande de mod&eacute;ration effectu&eacute;e</div>';
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

function change_post_confidentiality_status() // Attention, g�n�rique, s'applique et aux posts et aux commentaires
{
	$thread_id=-1;
	$comment_id=-1;
	$choice=-1;

	// R�cup�ration des arguments
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
	
	if (user_privilege_level()>2) // Le demandeur doit-�tre logg� et poss�der des droits d'�criture
    {
		if (($thread_id*$comment_id)>0) // Aucun objet d�sign�s, ou deux types d'objet d�sign�s � la fois
		{
			$_SESSION['transient_display']='<div class="warning">Impossible de d&eacute;terminer l\'objet auquel appliquer la commande de mod&eacute;ration</div>';
		}
		else
		{
			$result="";
			
			// V�rification de l'appartenance de l'ID au demandeur (pourra�t �tre int�gr� � la requ�te SQL si n�cessaire)
			if ($thread_id>0) // L'ordre s'applique � un post
			{
				$result=@mysql_query(sprintf("SELECT rand_prop,hash_prop FROM thread WHERE thread_id='%s'",mysql_real_escape_string($thread_id)));
			}
			else // L'ordre s'applique � un commentaire
			{
				$result=@mysql_query(sprintf("SELECT rand_prop,hash_prop FROM comment WHERE comment_id='%s'",mysql_real_escape_string($comment_id)));
			}
			
			// L'ID existe bien
			if ($result && $row=mysql_fetch_assoc($result))
			{
				// On v�rifie l'appartenance au demandeur
				if(check_property($row["rand_prop"],$row["hash_prop"]))
				{
					$query="";
					// Commande de mise � jour
					if ($thread_id>0) // L'ordre s'applique � un post
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
					else // L'ordre s'applique � un commentaire
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
					
					// La d�cision n'�tait ni 0 ni 1
					if (empty($query))
					{
						$_SESSION['transient_display']='<div class="warning">D&eacute;cision non valide</div>';
					}
					else
					{
						// On ex�cute la commande et note le r�sultat
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

	if (user_privilege_level()>2) // Il faut �tre logg� et poss�der des droits d'�criture
    {
		if (!($choice==-1 || $choice==0 || $choice==1))
		{
			$_SESSION['transient_display']='<div class="warning">Demande de vote incorrecte</div>';
		}
		elseif ($thread_id>0)
		{
			// S�lection d'un �ventuel vote dont on serait propri�taire pour ce post
			$result=@mysql_query(sprintf("SELECT vote_id, vote FROM vote WHERE thread_id='%s' AND CAST(SHA1(CONCAT('%s',CAST(rand_prop AS CHAR))) AS CHAR)=hash_prop",mysql_real_escape_string($thread_id),mysql_real_escape_string($_SESSION['login_c'])));
			if ($result)
			{
				$vote_prec=0; // On part du principe qu'on n'a pas vot� au pr�alable
				$id_vote=-1; // L'id est mis � jour si un vote est retrouv�
				if ($row=mysql_fetch_assoc($result))
				{
					$id_vote=$row["vote_id"];
					if($row["vote"]==1) // On a vot� pour au pr�alable
					{
						$vote_prec=1;
					}
					elseif($row["vote"]==0) // On a vot� contre au pr�alable
					{
						$vote_prec=-1;
					}
				}

				if($choice==-1)
				{
					if($vote_prec==-1) // On a d�j� vot� contre
					{
						$_SESSION['transient_display']='<div class="warning">Vote d&eacute;j&agrave; enregistr&eacute;</div>';
					}
					elseif($vote_prec==0) // On souhaite voter pour la premi�re fois contre
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
					elseif($vote_prec==1) // On souhaite passer d'un vote pour � un vote contre
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
					if($vote_prec==-1) // On souhaite passer d'un vote contre � un vote pour
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
					elseif($vote_prec==0) // On souhaite voter pour la premi�re fois pour
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
					elseif($vote_prec==1) // On a d�j� vot� pour
					{
						$_SESSION['transient_display']='<div class="warning">Vote d&eacute;j&agrave; enregistr&eacute;</div>';
					}
				}
				@mysql_free_result($result);
			}
			else // Mieux vaux ne pas continuer si l'on n'a pas pu v�rifier ce qui existait en base
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

		// Valeurs r�introduites dans le formulaire en cas d'erreur
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

		// Le formulaire a �t� valid�
		if (isset($_POST['form_name']) && $_POST['form_name']=="create_thread")
		{
			$check_1=(isset($_POST["title"]) && !empty($_POST["title"]));
            $check_2=(isset($_POST["message"]) && !empty($_POST["message"]));
			$check_3=(!isset($_POST["anonymization"]) || $_POST["anonymization"]=="on");
            $check_4=(isset($_POST["category"]) && is_numeric($_POST["category"]) && $_POST["category"]>0);
 
			// V�rification des arguments
			if ($check_1)
			{
				$title_prec=$_POST["title"];
			}
			else
			{
				echo('<div class="warning">Titre incorrect</div>');
			}                
			if ($check_2)
			{
				$text_prec=$_POST["message"];
			}
			else
			{
				echo('<div class="warning">Message incorrect</div>');
			}               
			if ($check_3)
			{
				if (isset($_POST["anonymization"]))
				{
					$anon_prec="on";
				}
			}
			else
			{
				echo('<div class="warning">Valeur pour l\'anonymat incorrecte</div>');
			}
			if ($check_4)
			{
				$cate_prec=$_POST["category"];
			}
			else
			{
				echo('<div class="warning">Cat&eacute;gorie incorrecte</div>');
			}
            
			if ($check_1 && $check_2 && $check_3 && $check_4) // Tous les arguments sont corrects, ex�cution du traitement du formulaire
			{
                $title_prec_sec=mysql_real_escape_string($title_prec);
                $text_prec_sec=mysql_real_escape_string($text_prec);
                $cate_prec_sec=mysql_real_escape_string($cate_prec);
                $rand_prop=mt_rand(0,65535);
                $hash_prop=sha1($_SESSION['login_c'].$rand_prop);

                if ($anon_prec=="on")
                {
                    $name_print="";
                }
                else
                {
                    $name_print=mysql_real_escape_string(construct_name_from_session());
                }

                if (@mysql_query("INSERT INTO `thread` (`thread_id`,`rand_prop`,`hash_prop`,`title`,`text`,`date`,`category`,`is_valid`,`possibly_name`) VALUES (NULL, '$rand_prop', '$hash_prop','$title_prec_sec','$text_prec_sec',CURRENT_TIMESTAMP,'$cate_prec_sec',0,'$name_print')"))
                {
                    echo('<div class="success">Proposition correctement plac&eacute;e en attente de mod&eacute;ration</div>');
					$affich_form=false;
                }
                else
                {
                    echo('<div class="warning">Erreur lors de la requ&ecirc;te</div>');
                }
            }
		}
			
		if ($affich_form) // Affichage du formulaire en incluant d'�ventuelles valeurs
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
            Note : L\'anonymat repose sur un m&eacute;canisme utilisant une valeur al&eacute;atoirement attribu&eacute;e &agrave; chaque 
			proposition. En pratique, vous pourrez donc &agrave; tout moment &eacute;diter votre message, le supprimer, l\'anonymiser ou 
			au contraire faire afficher votre nom. Mais dans le cas o&ugrave; vous activez l\'anonymisation, strictement personne, administrateurs compris, 
			ne sera capable de vous associer &agrave; un message donn&eacute; &agrave; partir des seules informations stock&eacute;es par le site.
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
		// Titre et messages �ventuels
		echo('<h1>Consultation des propositions :</h1>');
		
		if(isset($_SESSION['transient_display']))
		{
			echo($_SESSION['transient_display']);
			unset($_SESSION['transient_display']);
		}
	
		echo('
		
		<form method="post" action="?action=post_filter_change">
			<table class="tab_form">
				<tr>
					<td>
						Cat&eacute;gorie :
					</td>
					<td>
						<select name="category_filter">
							<option value="0">Toutes</option>');
					
		$result=@mysql_query("SELECT category_id,category_name FROM thread_category"); // Menu d�roulant de choix de cat�gorie en fonction de ce qui est disponible en base
		$tail='';
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
		
		echo($tail.'			
						</select>
					</td>
					<td>
						Trier par :
					</td>
					<td>
						<select name="sorting">');

		if (isset($_SESSION["thread_ordering"])) // Menu d�roulant de choix de l'ordre d'affichage
		{
			$thread_ordering=$_SESSION["thread_ordering"];
			if($thread_ordering==1)
			{
				$tail="<option value=\"1\" selected=\"selected\">Date</option><option value=\"2\">Nombre de votes</option><option value=\"3\">Proportion de votes favorables</option>";
			}
			elseif($thread_ordering==2)
			{
				$tail="<option value=\"1\">Date</option><option value=\"2\" selected=\"selected\">Nombre de votes</option><option value=\"3\">Proportion de votes favorables</option>";
			}
			elseif($thread_ordering==3)
			{
				$tail="<option value=\"1\">Date</option><option value=\"2\">Nombre de votes</option><option value=\"3\" selected=\"selected\">Proportion de votes favorables</option>";
			}
		}
		else
		{
			$tail="<option value=\"1\">Date</option><option value=\"2\">Nombre de votes</option><option value=\"3\">Proportion de votes favorables</option>";
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
		</form><br />');	

		$is_admin=($privileges>3);
		// Construction de la requ�te de rappatriement des thread
		$query_p1="(SELECT T.thread_id, T.rand_prop, T.hash_prop, T.title, T.text, T.date, T.is_valid, T.possibly_name, T.already_mod, G.category_name,
				SUM(V.vote) AS pro_vote, COUNT(V.vote) AS total_vote
				FROM thread T, thread_category G, vote V
				WHERE V.thread_id=T.thread_id AND G.category_id=T.category";
		$query_p2="(SELECT T.thread_id, T.rand_prop, T.hash_prop, T.title, T.text, T.date, T.is_valid, T.possibly_name, T.already_mod, G.category_name,
				0 AS pro_vote, 0 AS total_vote
				FROM thread T, thread_category G 
				WHERE T.thread_id <> ALL (SELECT thread_id FROM vote) AND G.category_id=T.category";
		$query_count="SELECT COUNT(T.thread_id) AS NUM_RES FROM thread T, thread_category G WHERE G.category_id=T.category";
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
		if (isset($_SESSION["thread_category_filter"]) && $_SESSION["thread_category_filter"]>0)
		{
			$category_searched=mysql_real_escape_string($_SESSION["thread_category_filter"]);
			$query_p1.=" AND T.category=$category_searched";
			$query_p2.=" AND T.category=$category_searched";
			$query_count.=" AND T.category=$category_searched";
		}
		
		$query=$query_p1.' GROUP BY T.thread_id, T.rand_prop, T.hash_prop, T.title, T.text, T.date, T.is_valid, T.possibly_name, G.category_name) UNION '.$query_p2.')';
			
		// D�termination du nombre r�sultats potentiellement retourn�s, pour conna�tre la r�partition par pages
		$num_res=-1; // Valeur par d�faut en cas d'�chec
		$res=@mysql_query($query_count);
		if ($res)
		{
			if($num_res=mysql_fetch_assoc($res))
			{
				$num_res=$num_res["NUM_RES"];
			}
			@mysql_free_result($res);
		}
		
		// Suite de la construction de la requ�te, GROUP et ORDER BY
		if (isset($_SESSION["thread_ordering"]))
		{
			$ordering=$_SESSION["thread_ordering"];
			if ($ordering==2)
			{
				$query.=" ORDER BY total_vote DESC";
			}
			elseif ($ordering==3)
			{
				$query.=" ORDER BY pro_vote/total_vote DESC";
			}
			else
			{
				$query.=" ORDER BY date DESC";
			}
		}
		else
		{
			$query.=" ORDER BY date DESC";
		}
		
		// Fin de la construction de la requ�te, LIMIT selon la page affich�e
		$page_to_display=1;
		if (!isset($_SESSION["thread_page"])) // Affichage de la premi�re page par d�faut, correction des arguments
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
		if ($num_res>-1) // On a �t� capable de v�rifier combien de r�sultats �taient disponibles ; on ne limite pas la requ�te sinon, m�me si c'est plus long
		{
			$offset=round(10*($page_to_display-1));
			if ($offset>=$num_res) // On a d�pass�, sans doute par erreur, on retourne � la page 1
			{
				$offset=0;
				$_SESSION["thread_page"]=1;
			}
			$query.=" LIMIT $offset,10";
			//$query.=" LIMIT 5,10";
		}

		// Ex�cution de la requ�te et affichage des r�sultats
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

				// Hormis l'auteur ou un administrateur, affichage uniquement si le post a �t� mod�r�
                if ($is_valid || $is_proprio || $privileges>3)
                {
					if($need_separator)
					{
						echo('<div class="newsterminator">
								<hr />
							</div>');
					}
					$need_separator=true;
					// Titre et �tat de mod�ration
					echo('<span class="newstitle">
							'.htmlentities(stripslashes($row["title"])).'
						</span>');
					if ($privileges>3)
					{
						if ($already_mod)
						{
							if ($is_valid)
							{
								echo('<img src="rep_img/modere.png" alt="Mod&eacute;r&eacute;" class="imgtitlenews" />');
							}
							else
							{
								echo('<img src="rep_img/masque.png" alt="Masqu&eacute;" class="imgtitlenews" />');
							}
						}
						else
						{
							echo('<img src="rep_img/n_modere.png" alt="Non mod&eacute;r&eacute;" class="imgtitlenews" />');
						}
					}
					elseif ($is_proprio)
					{
						if ($already_mod)
						{
							if (!$is_valid)
							{
								echo('<img src="rep_img/masque.png" alt="Masqu&eacute;" class="imgtitlenews" />');
							}
						}
						else
						{
							echo('<img src="rep_img/n_modere.png" alt="Non mod&eacute;r&eacute;" class="imgtitlenews" />');
						}
					}
					
					// Votes
					if ($privileges>2) // L'utilisateur peut voter, liens de vote, lien d'annulation le cas �ch�ant
					{
						echo('<span class="vote">');
						if ($per_vote>0)
						{
							echo('<a href="?action=vote_post&amp;order=0&amp;thread_id='.htmlentities($thread_id_affiche).'"><img src="rep_img/bright_votepro.png" alt="+1" class="imgvote" /></a>');
						}
						else
						{
							echo('<a href="?action=vote_post&amp;order=1&amp;thread_id='.htmlentities($thread_id_affiche).'"><img src="rep_img/pale_votepro.png" alt="+1" class="imgvote" /></a>');
						}
						
						if ($per_vote<0)
						{
							echo('<a href="?action=vote_post&amp;order=0&amp;thread_id='.htmlentities($thread_id_affiche).'"><img src="rep_img/bright_voteneg.png" alt="-1" class="imgvote" /></a>');
						}
						else
						{
							echo('<a href="?action=vote_post&amp;order=-1&amp;thread_id='.htmlentities($thread_id_affiche).'"><img src="rep_img/pale_voteneg.png" alt="-1" class="imgvote" /></a>');
						}
						echo('</span>');
						
					}
					
					// Contexte
                    echo('<div class="newsundertitle">
							'.htmlentities(transfo_date($row["date"])).'&nbsp;-&nbsp;'.htmlentities($row["category_name"]));
					if ($is_proprio)
					{
						if (!empty($row["possibly_name"]))
						{
							echo('&nbsp;-&nbsp;<a href="?action=anonymization&amp;order=0&amp;thread_id='.htmlentities($thread_id_affiche).'">'.htmlentities($row["possibly_name"]).'</a>');
						}
						else
						{
							echo('&nbsp;-&nbsp;<a href="?action=anonymization&amp;order=1&amp;thread_id='.htmlentities($thread_id_affiche).'">Faire afficher mon nom</a>');
						}
					}
					elseif (!empty($row["possibly_name"]))
					{
						echo('&nbsp;-&nbsp;'.htmlentities($row["possibly_name"]));
					}
					echo('</div>
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
									<span class="votebarannotation">
										+'.htmlentities($pro_vote).'
									</span>
								</span>
							</div>');
						}
						else
						{
							$prop_pro=round(100*$pro_vote/($agt_vote+$pro_vote));
							echo('<span class="provote" style="height:'.$prop_pro.'%;width:8px;">
									<span class="votebarannotation">
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
					
					// Corps
                    echo('<div class="newscontent">'.nl2br(htmlentities(stripslashes($row["text"]))).'</div>');

					echo('<div class="newsendlinks">');
                    if ($is_proprio || $is_admin) // Administrateurs et propri�taires peuvent �diter et supprimer
                    {
						echo('
							<a href="?action=edit_post&amp;thread_id='.htmlentities($thread_id_affiche).'">Editer</a>
							<a href="?action=remove_post&amp;thread_id='.htmlentities($thread_id_affiche).'">Supprimer</a>');
						if ($is_admin) // L'administrateur peut afficher ou masquer le post
						{					
							if($is_valid || !$already_mod)
							{
								echo('<a href="?action=moderation&amp;order=0&amp;thread_id='.htmlentities($thread_id_affiche).'">Refuser</a>');
							}
							if(!$is_valid || !$already_mod)
							{
								echo('<a href="?action=moderation&amp;order=1&amp;thread_id='.htmlentities($thread_id_affiche).'">Accepter</a>');
							}
						}
                    }
					
					// Affichage des commentaires - ferme le div newsendlinks
					affichage_comments($thread_id);
                }
			}
			
			// Affichage vide / d'un cadre de choix de page / d'un avertissement sur le nombre de r�sultats / selon les cas
			if ($result_returned)
			{
				if ($num_res>10)
				{
					echo('<div class="bottom_page_choice">');
					for ($i=1;$i<ceil($num_res/10)+1;$i++)
					{
						if($i==$_SESSION["thread_page"])
						{
							echo("$i&nbsp;&nbsp;");
						}
						else
						{
							echo('<a href="?action=change_thread_page&amp;num_page='.$i.'">'.$i.'</a>&nbsp;&nbsp;');
						}
					}
					echo('</div>');
				}
			}
			else
			{
				echo('<div class="warning">Aucune proposition n\'est disponible selon les crit�res choisis</div>');
			}
			@mysql_free_result($result);
        }
        else
        {
            echo('<div class="warning">Erreur lors de la requ&ecirc;te</div>');
        }	
	}
	else
	{
		need_enpc_member_privilege(2);
	}
}

?>
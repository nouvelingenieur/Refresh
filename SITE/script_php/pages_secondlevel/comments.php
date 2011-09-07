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

include_once("tool.php");
include_once("errors.php");

function new_comment()
{
	if (isset($_SESSION["text_new_comment_rest"]))
	{
		unset($_SESSION["text_new_comment_rest"]);
	}
	if (isset($_SESSION["text_anonymous_rest"]))
	{
		unset($_SESSION["text_anonymous_rest"]);
	}
	
	if (user_privilege_level()>2) // Loggé et pas en "lecture seule"
	{
        $text_prec="";
        $anon_prec="";
		$succes_func=false;

		if (isset($_POST['form_name']) && $_POST['form_name']=="create_comment")
		{
            $check_1=(isset($_POST["message"]) && is_string($_POST["message"]) && !empty($_POST["message"]));
			$check_2=(!isset($_POST["anonymization"]) || $_POST["anonymization"]=="on");
 
			if ($check_1)
			{
				$text_prec=$_POST["message"];
			}
			else
			{
				$_SESSION['transient_display']='<div class="warning">Texte du nouveau commentaire incorrect</div>';
			}                
			if ($check_2)
			{
				if (isset($_POST["anonymization"]))
				{
					$anon_prec="on";
				}
			}
			else
			{
				$_SESSION['transient_display']='<div class="warning">Valeur pour l\'anonymat du nouveau commentaire incorrecte</div>';
			}              
			if ($check_1 && $check_2)
			{
				if (isset($_GET["thread_id"]) && is_numeric($_GET["thread_id"]) && $_GET["thread_id"]>0)
				{
					$thread_id=mysql_real_escape_string($_GET["thread_id"]);
					$result=@mysql_query(sprintf("SELECT thread_id FROM thread WHERE thread_id='%s'",$thread_id));
					if (!$result || mysql_num_rows($result)<1)
					{
						$_SESSION['transient_display']='<div class="warning">Proposition introuvable lors de l\'ajout du nouveau commentaire</div>';
					}
					else
					{
						$text_prec=mysql_real_escape_string($text_prec);
						$rand_prop=mt_rand(0,65535);
						$hash_prop=sha1($_SESSION['login_c'].$rand_prop); // Anonymat relatif, car nombre d'adresses mails élèves dans l'école limité...

						if ($anon_prec=="on")
						{
							$name_print="";
						}
						else
						{
							$name_print=mysql_real_escape_string(construct_name_from_session());
						}
						if (@mysql_query("INSERT INTO `comment` (`comment_id`,`thread_id`,`rand_prop`,`hash_prop`,`text`,`date`,`is_valid`,`possibly_name`) VALUES (NULL,'$thread_id','$rand_prop','$hash_prop','$text_prec',CURRENT_TIMESTAMP,0,'$name_print')"))
						{
							$_SESSION['transient_display']='<div class="success">Commentaire correctement plac&eacute; en attente de mod&eacute;ration</div>';
							$succes_func=true;
						}
						else
						{
							$_SESSION['transient_display']='<div class="warning">Erreur lors de la requ&ecirc;te d\'ajout du nouveau commentaire</div>';
						}
						@mysql_free_result($result);
					}
				}
				else
				{
					$_SESSION['transient_display']='<div class="warning">Proposition introuvable lors de l\'ajout du nouveau commentaire</div>';
					return;
				}
            }					
		}		
        if (isset($_POST))
        {
            unset($_POST);
        }
		if(!$succes_func)
		{
			$_SESSION["text_new_comment_rest"]=$text_prec;
			if (!empty($anon_prec))
			{
				$_SESSION["text_anonymous_rest"]=1;
			}
		}
	}
	else
	{
		$_SESSION['transient_display']='<div class="warning">Droits insuffisants pour ajouter un commentaire</div>';
	}
}

function deletion()
{
	$priv=user_privilege_level();
	if ($priv>2) // Loggé et pas en lecture seule (ne sera pas nécessairement suffisant)
	{
		echo('<h1>Suppression :</h1>');
	
		// Récupération des arguments
		$id=-1;
		$is_prop=0;
		$mess_user="";
		$warnings="";
		$type=0; // 0=thread, 1=comment
		
		$exist_t=isset($_GET["thread_id"]);
		$exist_c=isset($_GET["comment_id"]);
			
		if ($exist_c && $exist_t)
		{
			$warnings='<div class="warning">Impossible de d&eacute;terminer la cat&eacute;gorie de l\'objet &agrave; supprimer</div>';
		}
		elseif ($exist_c)
		{
			$type=1;
			if (is_numeric($_GET["comment_id"]) && $_GET["comment_id"]>0)
			{
				$result=@mysql_query(sprintf("SELECT comment_id,text,rand_prop,hash_prop FROM comment WHERE comment_id='%s'",mysql_real_escape_string($_GET["comment_id"])));
				if (!$result || mysql_num_rows($result)<1)
				{
					$warnings='<div class="warning">Commentaire inexistant</div>';
				}
				else
				{
					$row=mysql_fetch_assoc($result);
					$id=$row["comment_id"];
					$mess_user=$row["text"];
					$is_prop=check_property($row["rand_prop"],$row["hash_prop"]);
					@mysql_free_result($result);	
				}
			}
			else
			{
				$warnings='<div class="warning">Commentaire inexistant</div>';
			}	
		}
		elseif ($exist_t)
		{
			$type=0;
			if (is_numeric($_GET["thread_id"]) && $_GET["thread_id"]>0)
			{
				$result=@mysql_query(sprintf("SELECT thread_id,title,rand_prop,hash_prop FROM thread WHERE thread_id='%s'",mysql_real_escape_string($_GET["thread_id"])));
				if (!$result || mysql_num_rows($result)<1)
				{
					$warnings='<div class="warning">Proposition inexistante</div>';
				}
				else
				{
					$row=mysql_fetch_assoc($result);
					$id=$row["thread_id"];
					$mess_user=$row["title"];
					$is_prop=check_property($row["rand_prop"],$row["hash_prop"]);
					@mysql_free_result($result);
				}
			}
			else
			{
				$warnings='<div class="warning">Proposition inexistante</div>';
			}	
		}
		else
		{
			$warnings='<div class="warning">Id de l\'objet non pr&eacute;cis&eacute;</div>';
		}
		
		if (empty($warnings) && $id>0) // Titre ou corps éventuellement vide, ce n'est pas une condition
		{
			if (isset($_SESSION['post']))
			{
				$_POST=$_SESSION['post'];
				unset($_SESSION['post']);
			}

			// Traitement d'un formulaire éventuellement déjà validé
			$affich_form=true;
			if (isset($_POST['form_name']) && $_POST['form_name']=="deletion")
			{
				if(!isset($_POST["validation"]))
				{
					echo('<div class="warning">Vous n\'avez pas confirm&eacute; la suppression</div>');
				}
				elseif($_POST["validation"]=="on")
				{
					if ($priv>4 || $is_prop==1)
					{
						if ($type==0)
						{
							if (@mysql_query(sprintf("DELETE FROM thread WHERE thread_id='%s'",mysql_real_escape_string($id)))) // Pas besoin de s'embêter avec les commentaires/votes associés, tout est en cascade avec des clés étrangères
							{
								echo('<div class="success">Proposition correctement supprim&eacute;e</div>');
								$affich_form=false;
							}
							else
							{
								echo('<div class="warning">Erreur lors de la suppression de la proposition</div>');
							}
						}
						else
						{
							if (@mysql_query(sprintf("DELETE FROM comment WHERE comment_id='%s'",mysql_real_escape_string($id))))
							{
								echo('<div class="success">Commentaire correctement supprim&eacute;</div>');
								$affich_form=false;
							}
							else
							{
								echo('<div class="warning">Erreur lors de la suppression du commentaire</div>');
							}
						}
					}
					else
					{
						echo('<div class="warning">Vous ne disposez pas des droits n&eacute;cessaires</div>');
					}
				}
			}
			
			// Affichage du formulaire le cas échéant
			if ($affich_form)
			{
				if ($priv>4 || $is_prop==1)
				{
					if ($type==0)
					{
						echo('<form method="post" action="?action=remove_post&amp;thread_id='.htmlentities($id).'">');
						echo('<br />Souhaitez-vous r&eacute;ellement supprimer la proposition suivante ?<br /><br />"');
					}
					else
					{
						echo('<form method="post" action="?action=remove_post&comment_id='.htmlentities($id).'">');
						echo('Souhaitez-vous r&eacute;ellement supprimer le commentaire suivant ?<br />"');
					}
					echo(nl2br(htmlentities(stripslashes($mess_user))).'"<br /><br />');
					echo('<input type="checkbox" name="validation" id="v_check" /><label for="v_check">Oui, supprimer !</label>');
					echo('<input type="hidden" name="form_name" value="deletion" />&nbsp;&nbsp;&nbsp;&nbsp;<input type="submit" value="Valider" /></form>');
				}
				else
				{
					echo('<div class="warning">Vous ne disposez pas des droits n&eacute;cessaires</div>');
				}
			}	
		}
		elseif (!empty($warnings))
		{
			echo($warnings);
		}
		
        if (isset($_POST))
        {
            unset($_POST);
        }
	}
	else
	{
		need_logged_member_privilege();
	}
}

function edition()
{
	$priv=user_privilege_level();
	if ($priv>2)
	{
		echo('<h1>Edition :</h1>');
	
		// Récupération des arguments
		$id=-1;
		$is_prop=0;
		$mess_user="";
		$title_prec="";
		$cate_prec="";
		$warnings="";
		$type=0; // 0=thread, 1=comment
		
		$exist_t=isset($_GET["thread_id"]);
		$exist_c=isset($_GET["comment_id"]);
			
		if ($exist_c && $exist_t)
		{
			$warnings='<div class="warning">Impossible de d&eacute;terminer la cat&eacute;gorie de l\'objet &agrave; &eacute;diter</div>';
		}
		elseif ($exist_c)
		{
			$type=1;
			if (is_numeric($_GET["comment_id"]) && $_GET["comment_id"]>0)
			{
				$comment_id=mysql_real_escape_string($_GET["comment_id"]);
				$result=@mysql_query(sprintf("SELECT comment_id,text,rand_prop,hash_prop FROM comment WHERE comment_id='%s'",mysql_real_escape_string($comment_id)));
				if (!$result || mysql_num_rows($result)<1)
				{
					$warnings='<div class="warning">Commentaire inexistant</div>';
				}
				else
				{
					$row=mysql_fetch_assoc($result);
					$id=$row["comment_id"];
					$mess_user=$row["text"];
					$is_prop=check_property($row["rand_prop"],$row["hash_prop"]);
					@mysql_free_result($result);
				}
			}
			else
			{
				$warnings='<div class="warning">Commentaire inexistant</div>';
			}	
		}
		elseif ($exist_t)
		{
			$type=0;
			if (is_numeric($_GET["thread_id"]) && $_GET["thread_id"]>0)
			{
				$thread_id=mysql_real_escape_string(($_GET["thread_id"]));
				$result=@mysql_query(sprintf("SELECT thread_id,text,title,category,rand_prop,hash_prop FROM thread WHERE thread_id='%s'",$thread_id));
				if (!$result || mysql_num_rows($result)<1)
				{
					$warnings='<div class="warning">Proposition inexistante</div>';
				}
				else
				{
					$row=mysql_fetch_assoc($result);
					$id=$row["thread_id"];
					$mess_user=$row["text"];
					$is_prop=check_property($row["rand_prop"],$row["hash_prop"]);
					$title_prec=$row["title"];
					$cate_prec=$row["category"];
					@mysql_free_result($result);
				}
			}
			else
			{
				$warnings='<div class="warning">Proposition inexistante</div>';
			}	
		}
		else
		{
			$warnings='<div class="warning">Id de l\'objet non pr&eacute;cis&eacute;</div>';
		}
		
		if (empty($warnings) && $id>0) // Titre ou corps éventuellement vide, ce n'est pas une condition
		{
			if (isset($_SESSION['post']))
			{
				$_POST=$_SESSION['post'];
				unset($_SESSION['post']);
			}

			// Traitement d'un formulaire éventuellement déjà validé
			$affich_form=true;
			if (isset($_POST['form_name']) && $_POST['form_name']=="edition")
			{
				if ($priv>4 || $is_prop==1)
				{
					// Afficher les messages d'erreur en une fois, traitement parallèle
					if(isset($_POST["message"]) && is_string($_POST["message"]) && !empty($_POST["message"]))
					{
						$mess_user=$_POST["message"];
						if ($type==0)
						{
							if (isset($_POST["title"]) && is_string($_POST["title"]) && !empty($_POST["title"]))
							{
								if (isset($_POST["category"]) && is_numeric($_POST["category"]) && $_POST["category"]>0)
								{
									$title_prec=$_POST["title"];
									$cate_prec=$_POST["category"];
									if (@mysql_query(sprintf("UPDATE thread SET is_valid=0,text='%s',title='%s',category='%s',already_mod=0 WHERE thread_id='%s'",mysql_real_escape_string($mess_user),mysql_real_escape_string($title_prec),mysql_real_escape_string($cate_prec),mysql_real_escape_string($thread_id))))
									{
										echo('<div class="success">Proposition correctement modifi&eacute;e</div>');
										$affich_form=false;
									}
									else
									{
										echo('<div class="warning">Erreur lors de l\'&eacute;dition</div>');
									}
								}	
								else
								{
									echo('<div class="warning">Cat&eacute;gorie incorrecte</div>');
								}
							}
							else
							{
								echo('<div class="warning">Titre incorrect</div>');
							}
						}
						else
						{
							if (@mysql_query(sprintf("UPDATE comment SET is_valid=0,text='%s',already_mod=0 WHERE comment_id='%s'",mysql_real_escape_string($mess_user),mysql_real_escape_string($comment_id))))
							{
								echo('<div class="success">Commentaire correctement modifi&eacute;</div>');
								$affich_form=false;
							}
							else
							{
								echo('<div class="warning">Erreur lors de l\'&eacute;dition du commentaire</div>');
							}
						}		
					}
					else
					{
						echo('<div class="warning">Message incorrect</div>');
					}
				}
				else
				{
					echo('<div class="warning">Vous ne disposez pas des droits n&eacute;cessaires</div>');
				}
			}
			
			// Affichage du formulaire le cas échéant
			if ($affich_form)
			{
				if ($priv>4 || $is_prop==1)
				{
					if ($type==0)
					{
						echo('<form method="post" action="?action=edit_post&amp;thread_id='.htmlentities($id).'"><table class="tab_form">');
					}
					else
					{
						echo('<form method="post" action="?action=edit_post&amp;comment_id='.htmlentities($id).'"><table class="tab_form">');				
					}

					if ($type==0)
					{
						echo('<tr>
								<td>
									Titre :
								</td>
								<td>');
						if (empty($title_prec))
						{
							echo('<input type="text" name="title" />');
						}
						else
						{
							echo('<input type="text" name="title" value="'.htmlentities(stripslashes($title_prec)).'" />');
						}
						echo('</td>
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
								if($cate_prec==$row["category_id"])
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

						echo($tail.'</select>
								</td>
							</tr>
							');			
					}	
					echo('<tr>
								<td colspan="2">
									<textarea name="message" rows="15" cols="80">'.htmlentities(stripslashes($mess_user)).'</textarea>
								</td>
							</tr>
							<tr>
								<td colspan="2">
									<input type="hidden" name="form_name" value="edition" />
								</td>
							</tr>
							<tr class="submit_center">
								<td colspan="2" rowspan="1">
									<input type="submit" value="Valider" />
								</td>
							</tr>
						</table>
					</form>');
				}
				else
				{
					echo('<div class="warning">Vous ne disposez pas des droits n&eacute;cessaires</div>');
				}
			}			
		}
		elseif (!empty($warnings))
		{
			echo($warnings);
		}
		
        if (isset($_POST))
        {
            unset($_POST);
        }
	}
	else
	{
		need_logged_member_privilege();
	}
}

function affichage_comments($thread_id,$moderation_mode)
{
	$privileges=user_privilege_level();
	$is_admin=($privileges>3);
	$ancre=htmlentities($thread_id);
	
	if ($moderation_mode)
	{
		if ($is_admin)
		{
			$result=@mysql_query("SELECT comment_id,rand_prop,hash_prop,text,date,possibly_name FROM comment WHERE already_mod=0 ORDER BY DATE DESC");		
			if($result)
			{
				$result_returned=false;
				while($row=mysql_fetch_assoc($result))
				{
					$result_returned=true;
					$is_proprio=check_property($row["rand_prop"],$row["hash_prop"]);
					$ancre=htmlentities($row["comment_id"]);

					// Informations de contexte
					echo('<div class="newscomment" id="'.$ancre.'">
							<span class="newsundertitle">
								'.htmlentities(transfo_date($row["date"])));									
					if (!empty($row["possibly_name"]))
					{
						echo('&nbsp;-&nbsp;'.htmlentities($row["possibly_name"]));
					}
					echo('</span>');		
					
					// Etat de modération
					echo('<img src="rep_img/n_modere.png" alt="Non mod&eacute;r&eacute;" class="imgtitlecomment" />');

					// Corps du commentaire
					echo('<div class="newscommentcontent">'.text_display_prepare($row["text"]).'</div>');
					
					// Liens administratifs sur le commentaire
					echo('<div class="newsendlinks">');
					if ($is_proprio || $privileges>4)
					{
						echo('
							<a href="?action=edit_post&amp;comment_id='.$ancre.'">Editer</a>
							<a href="?action=remove_post&amp;comment_id='.$ancre.'">Supprimer</a>');
							if ($is_proprio)
							{
								if (!empty($row["possibly_name"]))
								{
									echo('<a href="?action=anonymization&amp;order=0&amp;comment_id='.$ancre.'#'.$ancre.'">Masquer mon nom</a>');
								}
								else
								{
									echo('<a href="?action=anonymization&amp;order=1&amp;comment_id='.$ancre.'#'.$ancre.'">Afficher mon nom</a>');
								}
							}
					}
					echo('
							<a href="?action=moderation&amp;order=0&amp;comment_id='.$ancre.'">Refuser</a>
							<a href="?action=moderation&amp;order=1&amp;comment_id='.$ancre.'">Accepter</a>
						</div>
					</div>');
				}
				if(!$result_returned)
				{
					echo('<div class="warning">Aucun commentaire n\'est disponible selon les critères choisis</div>');
				}
			}
			else
			{
				echo('<div class="warning">Erreur lors de la recherche des commentaires non mod&eacute;r&eacute;s</div>');
			}
			@mysql_free_result($result);
		}
		else
		{
			echo('<div class="warning">Vous ne disposez pas des droits n&eacute;cessaires</div>');
		}
	}
	else
	{
		if ($privileges>1)
		{
			if (isset($_SESSION["unroll_comment"]) && $_SESSION["unroll_comment"]==$thread_id)
			{
				$result=@mysql_query(sprintf("SELECT comment_id,rand_prop,hash_prop,text,date,is_valid,already_mod,possibly_name FROM comment WHERE thread_id='%s'",mysql_real_escape_string($thread_id)));
				if($result)
				{
					if($privileges>3)
					{
						$result_temp=@mysql_query(sprintf("SELECT COUNT(*) AS NB_COMMENT FROM comment WHERE thread_id='%s'",mysql_real_escape_string($thread_id)));
					}
					else
					{
						if (is_logged())
						{
							$result_temp=@mysql_query(sprintf("SELECT COUNT(*) AS NB_COMMENT FROM comment WHERE thread_id='%s' AND (is_valid=1 OR (CAST(SHA1(CONCAT('%s',CAST(rand_prop AS CHAR))) AS CHAR)=hash_prop))",mysql_real_escape_string($thread_id),mysql_real_escape_string($_SESSION['login_c'])));	
						}
						else
						{
							$result_temp=@mysql_query(sprintf("SELECT COUNT(*) AS NB_COMMENT FROM comment WHERE is_valid=1 AND thread_id='%s'",mysql_real_escape_string($thread_id)));
						}
					}
					if($result && $row=mysql_fetch_assoc($result_temp))
					{
						if($row["NB_COMMENT"]==0)
						{
							echo('<div class="speccom">
										<a href="?action=unrollcomment&amp;order=0&amp;thread_id='.htmlentities($thread_id).'#'.$ancre.'">
											<span class="newslinkcomment_inactive">
												0&nbsp;commentaires
											</span>
											<img src="rep_img/roll_arrow_inactive.png" alt="Masquer" class="imglinknews" />
										</a>
									</div>
								</div>');
						}
						elseif($row["NB_COMMENT"]==1)
						{
							echo('<div class="speccom">
										<a href="?action=unrollcomment&amp;order=0&amp;thread_id='.htmlentities($thread_id).'#'.$ancre.'">
											<span class="newslinkcomment_roll">
												1&nbsp;commentaire
											</span>
											<img src="rep_img/roll_arrow.png" alt="Masquer" class="imglinknews" />
										</a>
									</div>
								</div>');
						}
						else
						{
							echo('<div class="speccom">
										<a href="?action=unrollcomment&amp;order=0&amp;thread_id='.htmlentities($thread_id).'#'.$ancre.'">
											<span class="newslinkcomment_roll">
												'.htmlentities($row["NB_COMMENT"]).'&nbsp;commentaires
											</span>
											<img src="rep_img/roll_arrow.png" alt="Masquer" class="imglinknews" />
										</a>
									</div>
								</div>');
						}
					}
					else
					{
						echo('<div class="speccom">
									<a href="?action=unrollcomment&amp;order=0&amp;thread_id='.'#'.$ancre.htmlentities($thread_id).'">
										<span class="newslinkcomment_roll">
											?&nbsp;commentaires
										</span>	
										<img src="rep_img/roll_arrow.png" alt="Masquer" class="imglinknews" />
									</a>
								</div>
							</div>');
					}
					@mysql_free_result($result_temp);
					while($row=mysql_fetch_assoc($result))
					{
						$is_proprio=check_property($row["rand_prop"],$row["hash_prop"]);
						$is_valid=$row["is_valid"];
						$already_mod=$row["already_mod"];
						$comment_id=$row["comment_id"];
						if ($is_valid || $is_proprio || $privileges>3)
						{
							// Informations de contexte
							echo('<div class="newscomment">
									<span class="newsundertitle">
										'.htmlentities(transfo_date($row["date"])));									
							if (!empty($row["possibly_name"]))
							{
								echo('&nbsp;-&nbsp;'.htmlentities($row["possibly_name"]));
							}
							echo('</span>');		
							
							// Etat de modération
							if ($privileges>3)
							{
								if ($already_mod)
								{
									if ($is_valid)
									{
										echo('<img src="rep_img/modere.png" alt="Mod&eacute;r&eacute;" class="imgtitlecomment" />');
									}
									else
									{
										echo('<img src="rep_img/masque.png" alt="Masqu&eacute;" class="imgtitlecomment" />');
									}
								}
								else
								{
									echo('<img src="rep_img/n_modere.png" alt="Non mod&eacute;r&eacute;" class="imgtitlecomment" />');
								}
							}
							elseif ($is_proprio)
							{
								if ($already_mod)
								{
									if (!$is_valid)
									{
										echo('<img src="rep_img/masque.png" alt="Masqu&eacute;" class="imgtitlecomment" />');
									}
								}
								else
								{
									echo('<img src="rep_img/n_modere.png" alt="Non mod&eacute;r&eacute;" class="imgtitlecomment" />');
								}
							}
							
							// Corps du commentaire
							echo('<div class="newscommentcontent">'.text_display_prepare($row["text"]).'</div>');
							
							// Liens administratifs sur le commentaire
							if ($is_proprio || $is_admin)
							{
								echo('<div class="newsendlinks">');
								if ($is_proprio || $privileges>4)
								{
									echo('<a href="?action=edit_post&amp;comment_id='.htmlentities($comment_id).'">Editer</a>
									<a href="?action=remove_post&amp;comment_id='.htmlentities($comment_id).'">Supprimer</a>');
									if ($is_proprio)
									{
										if (!empty($row["possibly_name"]))
										{
											echo('<a href="?action=anonymization&amp;order=0&amp;comment_id='.htmlentities($comment_id).'#'.$ancre.'">Masquer mon nom</a>');
										}
										else
										{
											echo('<a href="?action=anonymization&amp;order=1&amp;comment_id='.htmlentities($comment_id).'#'.$ancre.'">Afficher mon nom</a>');
										}
									}
								}
								if ($is_admin) // L'administrateur peut afficher ou masquer le post
								{
									if($is_valid || !$already_mod)
									{
										echo('<a href="?action=moderation&amp;order=0&amp;comment_id='.htmlentities($comment_id).'#'.$ancre.'">Refuser</a>');
									}
									if(!$is_valid || !$already_mod)
									{
										echo('<a href="?action=moderation&amp;order=1&amp;comment_id='.htmlentities($comment_id).'#'.$ancre.'">Accepter</a>');
									}			
								}
								echo('</div>');
							}
							echo('</div>');
						}
					}
					$text_prec="";
					$anon_prec="";
					if (isset($_SESSION["text_new_comment_rest"]))
					{
						$text_prec=$_SESSION["text_new_comment_rest"];
					}
					if (isset($_SESSION["text_anonymous_rest"]))
					{
						$anon_prec=1;
					}
					if ($privileges>2)
					{
						echo('<div class="newsformcomment">
								<form method="post" action="?action=comment_post&amp;thread_id='.htmlentities($thread_id).'#'.$ancre.'"><p>
									<textarea name="message" rows="15" cols="80">'.htmlentities($text_prec).'</textarea>
										<span class="checkcommentform">');
						if (empty($anon_prec))
						{
							echo('<input type="checkbox" name="anonymization" />');
						}   
						else
						{
							echo('<input type="checkbox" name="anonymization" checked="checked" />');
						}

						echo('Anonymiser le commentaire
									</span>
									<input type="hidden" name="form_name" value="create_comment" />
									<span class="validatecommentform">
										<input type="submit" value="Valider" />
									</span>
								</p></form>
							</div>');
					}
					if (isset($_SESSION["text_new_comment_rest"]))
					{
						unset($_SESSION["text_new_comment_rest"]);
					}
					if (isset($_SESSION["text_anonymous_rest"]))
					{
						unset($_SESSION["text_anonymous_rest"]);
					}
					@mysql_free_result($result);
				}
				else
				{
					echo('<div class="warning">Erreur lors de la recherche des commentaires</div></div>');
				}
			}
			else
			{
				if($privileges>3)
				{
					$result=@mysql_query(sprintf("SELECT COUNT(*) AS NB_COMMENT FROM comment WHERE thread_id='%s'",mysql_real_escape_string($thread_id)));
				}
				else
				{	
					if (is_logged())
					{
						$result=@mysql_query(sprintf("SELECT COUNT(*) AS NB_COMMENT FROM comment WHERE thread_id='%s' AND (is_valid=1 OR (CAST(SHA1(CONCAT('%s',CAST(rand_prop AS CHAR))) AS CHAR)=hash_prop))",mysql_real_escape_string($thread_id),mysql_real_escape_string($_SESSION['login_c'])));	
					}
					else
					{
						$result=@mysql_query(sprintf("SELECT COUNT(*) AS NB_COMMENT FROM comment WHERE is_valid=1 AND thread_id='%s'",mysql_real_escape_string($thread_id)));
					}
				}
				if($result && $row=mysql_fetch_assoc($result))
				{
					if($row["NB_COMMENT"]==0)
					{
						echo('<div class="speccom">
									<a href="?action=unrollcomment&amp;order=1&amp;thread_id='.htmlentities($thread_id).'#'.$ancre.'">
										<span class="newslinkcomment_inactive">
											0&nbsp;commentaires
										</span>
										<img src="rep_img/unroll_arrow_inactive.png" alt="Afficher" class="imglinknews" />
									</a>
								</div>
							</div>');
					}
					elseif($row["NB_COMMENT"]==1)
					{
						echo('<div class="speccom">
									<a href="?action=unrollcomment&amp;order=1&amp;thread_id='.htmlentities($thread_id).'#'.$ancre.'">
										<span class="newslinkcomment_unroll">
											1&nbsp;commentaire
										</span>
										<img src="rep_img/unroll_arrow.png" alt="Afficher" class="imglinknews" />
									</a>
								</div>
							</div>');
					}
					else
					{
						echo('<div class="speccom">
									<a href="?action=unrollcomment&amp;order=1&amp;thread_id='.htmlentities($thread_id).'#'.$ancre.'">
										<span class="newslinkcomment_unroll">
											'.htmlentities($row["NB_COMMENT"]).'&nbsp;commentaires
										</span>
										<img src="rep_img/unroll_arrow.png" alt="Afficher" class="imglinknews" />
									</a>
								</div>
							</div>');
					}
				}
				else
				{
					echo('<div class="warning">Erreur lors de la recherche des commentaires</div></div>');
				}
				@mysql_free_result($result);
			}
		}
	}
}

?>
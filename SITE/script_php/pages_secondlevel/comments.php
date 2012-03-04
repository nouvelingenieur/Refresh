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
				$text_prec=trim($_POST["message"]);
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
						$text_back=$text_prec;
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
						$chaine_conf=random_password(40);
						$chaine_conf_hash=sha1($chaine_conf);
						if (@mysql_query("INSERT INTO `comment` (`comment_id`,`thread_id`,`rand_prop`,`hash_prop`,`text`,`date`,`is_valid`,`possibly_name`,`chaine_moderation`) VALUES (NULL,'$thread_id','$rand_prop','$hash_prop','$text_prec',CURRENT_TIMESTAMP,0,'$name_print','$chaine_conf_hash')"))
						{
							$_SESSION['transient_display']='<div class="success">Commentaire correctement plac&eacute; en attente de mod&eacute;ration</div>';
							$succes_func=true;
							$comment_id=mysql_insert_id();
							/*
							$nexp="Ponts ParisTech Refresh";
							$email="webmaster_refresh@enpc.org";
							$subject="Modération - nouveau commentaire";
							$header = "From: ". $nexp . " <" . $email . ">\r\n";
							$text_backm=stripslashes($text_back);
							$mail_body =$mail_body = "Bonjour,\n\nUn nouveau commentaire a été ajouté en réponse à la proposition #$thread_id [http://refresh.enpc.org/index.php?action=display_post&unique=$thread_id]. Voici son contenu :\n\n****************\n$text_backm\n****************\n\nVous pouvez approuver ce commentaire dès maintenant en vous rendant à l'adresse http://refresh.enpc.org/?action=moderation_mail&type=comment&id=$comment_id&cconf=$chaine_conf\n\nCordialement,\n\nle site Refresh";
							file_put_contents('fichier.tmp.txt',$subject."\n\n\n\n".$mail_body);
							*/
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
			$_SESSION["text_new_comment_rest"]=$text_back;
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
					$mess_user=trim($row["text"]);
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
								$res_bonus=@mysql_query('SELECT MAX(thread_id) AS pot_max FROM thread');
								if ($res_bonus && $valtmp=mysql_fetch_assoc($res_bonus))
								{
									$valtmp=$valtmp['pot_max'];
									if($valtmp<$id)
										@mysql_query(sprintf("ALTER TABLE thread AUTO_INCREMENT=%s",mysql_real_escape_string($valtmp+1)));
								}
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
					$mess_user=trim($row["text"]);
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
					$mess_user=trim($row["text"]);
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
									$chaine_conf=random_password(40);
									$chaine_conf_hash=sha1($chaine_conf);
									if (@mysql_query(sprintf("UPDATE thread SET is_valid=0,text='%s',title='%s',category='%s',already_mod=0,chaine_moderation='%s' WHERE thread_id='%s'",mysql_real_escape_string($mess_user),mysql_real_escape_string($title_prec),mysql_real_escape_string($cate_prec),$chaine_conf_hash,mysql_real_escape_string($thread_id))))

									{
										echo('<div class="success">Proposition correctement modifi&eacute;e</div>');
										$affich_form=false;
										/*
										$nexp="Ponts ParisTech Refresh";
										$email="webmaster_refresh@enpc.org";
										$subject="Modération - proposition éditée";
										$header = "From: ". $nexp . " <" . $email . ">\r\n";
										$mess_userm=stripslashes($mess_user);
										$mail_body =$mail_body = "Bonjour,\n\nUne proposition a été éditée et doit être modérée [titre : '$title_prec']. Voici son contenu :\n\n****************\n$mess_userm\n****************\n\nVous pouvez l'approuver dès maintenant en vous rendant à l'adresse http://refresh.enpc.org/?action=moderation_mail&type=proposition&id=$thread_id&cconf=$chaine_conf\n\nCordialement,\n\nle site Refresh";
										file_put_contents('fichier.tmp.txt',$subject."\n\n\n\n".$mail_body);
										*/
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
							$chaine_conf=random_password(40);
							$chaine_conf_hash=sha1($chaine_conf);
							if (@mysql_query(sprintf("UPDATE comment SET is_valid=0,text='%s',already_mod=0,chaine_moderation='%s' WHERE comment_id='%s'",mysql_real_escape_string($mess_user),$chaine_conf_hash,mysql_real_escape_string($comment_id))))
							{
								echo('<div class="success">Commentaire correctement modifi&eacute;</div>');
								$affich_form=false;
								
								/*
								
								$nexp="Ponts ParisTech Refresh";
								$email="webmaster_refresh@enpc.org";
								$subject="Modération - commentaire édité";
								$header = "From: ". $nexp . " <" . $email . ">\r\n";
								$mess_userm=stripslashes($mess_user);
								
								$res_bonus=@mysql_query(sprintf("SELECT thread_id FROM comment WHERE comment_id='%s'",mysql_real_escape_string($comment_id)));
								if($res_bonus && $valtmp=mysql_fetch_assoc($res_bonus))
								{
									$validtmp=$valtmp["thread_id"];
									$mail_body =$mail_body = "Bonjour,\n\nUn commentaire en réponse à la proposition #$validtmp [http://refresh.enpc.org/index.php?action=display_post&unique=$validtmp] a été édité et doit être modéré. Voici son contenu :\n\n****************\n$mess_userm\n****************\n\nVous pouvez approuver ce commentaire dès maintenant en vous rendant à l'adresse http://refresh.enpc.org/?action=moderation_mail&type=comment&id=$comment_id&cconf=$chaine_conf\n\nCordialement,\n\nle site Refresh";
								}
								else
									$mail_body =$mail_body = "Bonjour,\n\nUn commentaire a été édité et doit être modéré. Voici son contenu :\n\n****************\n$mess_userm\n****************\n\nVous pouvez approuver ce commentaire dès maintenant en vous rendant à l'adresse http://refresh.enpc.org/?action=moderation_mail&type=comment&id=$comment_id&cconf=$chaine_conf\n\nCordialement,\n\nle site Refresh";
			
								//@mb_send_mail("moderateur_refresh@enpc.org",$subject,$mail_body,$header);
								file_put_contents('fichier.tmp.txt',$subject."\n\n\n\n".$mail_body);

								*/
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

function vote_comment()
{
	$comment_id=-1;
	$choice="";
	
	if (isset($_GET["comment_id"]))
	{
		$comment_id=$_GET["comment_id"];
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
		elseif ($comment_id>0)
		{
			// Sélection d'un éventuel vote dont on serait propriétaire pour ce post
			$result=@mysql_query(sprintf("SELECT vote_comment_id, vote FROM vote_comment WHERE comment_id='%s' AND CAST(SHA1(CONCAT('%s',CAST(rand_prop AS CHAR))) AS CHAR)=hash_prop",mysql_real_escape_string($comment_id),mysql_real_escape_string($_SESSION['login_c'])));
			if ($result)
			{
				$vote_prec=0; // On part du principe qu'on n'a pas voté au préalable
				$id_vote=-1; // L'id est mis à jour si un vote est retrouvé
				if ($row=mysql_fetch_assoc($result))
				{
					$id_vote=$row["vote_comment_id"];
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
						$thrad_id_sec=mysql_real_escape_string($comment_id);
						if (@mysql_query("INSERT INTO `vote_comment` (`vote_comment_id`,`comment_id`,`rand_prop`,`hash_prop`,`vote`) VALUES (NULL, '$thrad_id_sec','$rand_prop','$hash_prop','0')"))
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
						if (@mysql_query(sprintf("UPDATE vote_comment SET vote=0 WHERE vote_comment_id='%s'",mysql_real_escape_string($id_vote))))
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
						if(@mysql_query(sprintf("DELETE FROM vote_comment WHERE vote_comment_id='%s'",mysql_real_escape_string($id_vote))))
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
						if (@mysql_query(sprintf("UPDATE vote_comment SET vote=1 WHERE vote_comment_id='%s'",mysql_real_escape_string($id_vote))))
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
						$thrad_id_sec=mysql_real_escape_string($comment_id);
						if (@mysql_query("INSERT INTO `vote_comment` (`vote_comment_id`,`comment_id`,`rand_prop`,`hash_prop`,`vote`) VALUES (NULL, '$thrad_id_sec','$rand_prop','$hash_prop','1')"))
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

/*
Below are all the comment displaying functions
*/

// display number of comments button to roll or unroll the comments
function display_speccom($unique_mode,$ancre,$thread_id,$nb_comment,$roll) {
	if ($roll == 'roll') {
		$order = 0;
	} else {
		$order = 1;
	}

	echo('<div class="speccom">
				<a href="?action=unrollcomment'.(($unique_mode?'&amp;unique='.$ancre:'')).'&amp;order='.$order.'&amp;thread_id='.htmlentities($thread_id).'#a'.$ancre.'">
					<span class="newslinkcomment_'.$roll.'">');
	// display plural form with correct localization
	printf(ngettext("%d comment", "%d comments", $nb_comment), $nb_comment);
	echo('</span>
					<img src="rep_img/'.$roll.'_arrow.png" alt="Masquer" class="imglinknews" />
				</a>
			</div>
		</div>');
}

// display a comment with all its wrapper
function display_comment($is_proprio,$is_logged,$ancre,$date,$possibly_name,$my_vote,$my_provote,$total_vote,$thread_tmp,$text,$privileges,$sec_cid) {
	// display context
	echo('<div class="newscomment" id="'.$ancre.'"><a name="b'.$sec_cid.'" id="b'.$sec_cid.'"></a>
			<span class="newsundertitle">
				'.htmlentities(transfo_date($date)));

	// display author name if not anonymized
	if (!empty($possibly_name))
	{
		echo('&nbsp;-&nbsp;'.htmlentities($possibly_name));
	}
	
	// display link to post
	// echo('&nbsp;-&nbsp;[<a href="?action=display_post&amp;unique='.$thread_tmp.'">POST #'.$thread_tmp.'</a>]</span>');

	if ($is_logged)
	{
		// take care of the vote buttons
		$pro = 1; // vote pro button
		$agt = -1; // vote against button

		if ($my_vote>0)
			if ($my_provote>0)
				$pro = 0; // if I already voted pro I can unvote
			else
				$agt = 0; // if I already voted against I can unvote
				
		// display votes on comment
		echo('&nbsp;-&nbsp;[
			<a class="ntl" href="?action=vote_comment&amp;order='.$pro.'&amp;comment_id='.$ancre.'#b'.$ancre.'">+'.htmlentities($my_provote).'</a>
			/
			<a class="ntl" href="?action=vote_comment&amp;order='.$agt.'&amp;comment_id='.$ancre.'#b'.$ancre.'">-'.htmlentities($total_vote-$my_provote).'</a>]</span>');
	}
	else
	{
		echo('&nbsp;-&nbsp;[
			+'.htmlentities($my_provote).'
			/
			-'.htmlentities($total_vote-$my_provote).']</span>');
	}


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
	echo('<div class="newscommentcontent">'.$text.'</div>');
	
	// Liens administratifs sur le commentaire
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
					echo('<a href="?action=anonymization'.(($unique_mode?'&amp;unique='.$ancre:'')).'&amp;order=0&amp;comment_id='.$sec_cid.'#b'.$sec_cid.'">Masquer mon nom</a>');
				}
				else
				{
					echo('<a href="?action=anonymization'.(($unique_mode?'&amp;unique='.$ancre:'')).'&amp;order=1&amp;comment_id='.$sec_cid.'#b'.$sec_cid.'">Afficher mon nom</a>');
				}
			}
		}
		if ($is_admin) // L'administrateur peut afficher ou masquer le post
		{
			if($is_valid || !$already_mod)
			{
				echo('<a href="?action=moderation'.(($unique_mode?'&amp;unique='.$ancre:'')).'&amp;order=0&amp;comment_id='.$sec_cid.'#b'.$sec_cid.'">Refuser</a>');
			}
			if(!$is_valid || !$already_mod)
			{
				echo('<a href="?action=moderation'.(($unique_mode?'&amp;unique='.$ancre:'')).'&amp;order=1&amp;comment_id='.$sec_cid.'#b'.$sec_cid.'">Accepter</a>');
			}			
		}
		echo('</div>');
	}
	echo('</div>');

}

// display the form that allows users to post comments
function display_comment_form($unique_mode,$ancre,$thread_id,$text_prec,$anon_prec) {
	echo('<div class="newsformcomment">
			<form method="post" action="?action=comment_post'.(($unique_mode?'&amp;unique='.$ancre:'')).'&amp;thread_id='.htmlentities($thread_id).'#a'.$ancre.'"><p>
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

/*
 HERE STARTS THE MAIN FUNCTION THAT DISPLAYS COMMENTS
*/
// main comment displaying function
function affichage_comments($thread_id,$moderation_mode=false,$unique_mode=false)
{
	$privileges=user_privilege_level();
	$is_admin=($privileges>3);
	$is_logged=is_logged();
	$ancre=htmlentities($thread_id);
	
	if ($moderation_mode)
	{
		if ($is_admin)
		{
			$escaped_name=mysql_real_escape_string($_SESSION['login_c']);
			$result=@mysql_query(sprintf("(SELECT C.comment_id,C.rand_prop,C.hash_prop,C.text,C.date,C.possibly_name,C.thread_id,
			SUM(V.vote) AS pro_vote, COUNT(V.vote) AS total_vote, 
			MAX(CAST(SHA1(CONCAT('%s',CAST(V.rand_prop AS CHAR))) AS CHAR)=V.hash_prop) AS my_vote, 
			MAX(CAST(SHA1(CONCAT('%s',CAST(V.rand_prop AS CHAR))) AS CHAR)=V.hash_prop AND V.vote) AS my_provote
			FROM comment C, vote_comment V
			WHERE C.already_mod=0 AND V.comment_id=C.comment_id
			GROUP BY C.comment_id,C.rand_prop,C.hash_prop,C.text,C.date,C.possibly_name,C.thread_id)
			UNION
			(SELECT C.comment_id,C.rand_prop,C.hash_prop,C.text,C.date,C.possibly_name,C.thread_id,
			0 AS pro_vote, 0 AS total_vote,0 AS my_vote, 0 AS my_provote
			FROM comment C
			WHERE C.already_mod=0 AND C.comment_id<>ALL(SELECT comment_id FROM vote_comment))
			ORDER BY date ASC",$escaped_name,$escaped_name));
			if($result)
			{
				$result_returned=false;
				while($row=mysql_fetch_assoc($result))
				{
					$result_returned=true;
					$is_proprio=check_property($row["rand_prop"],$row["hash_prop"]);
					$ancre=htmlentities($row["comment_id"]);
					$date=$row['date'];
					$possibly_name=$row['possibly_name'];
					$sec_cid=htmlentities($row["comment_id"]);
					$thread_tmp=htmlentities($row["thread_id"]);
					$text=text_display_prepare(trim($row["text"]));

					// Informations de contexte
					display_comment($is_proprio,True,$ancre,$date,$possibly_name,$row['my_vote'],$row['my_provote'],$row['total_vote'],$thread_tmp,$text,$privileges,True,$sec_cid);
					
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
				$escaped_threadid=mysql_real_escape_string($thread_id);
				$escaped_name=(isset($_SESSION['login_c'])? mysql_real_escape_string($_SESSION['login_c']):'');
				$result=@mysql_query(sprintf("(SELECT C.comment_id,C.rand_prop,C.hash_prop,C.text,C.date,C.is_valid,C.already_mod,C.possibly_name,
				SUM(V.vote) AS pro_vote, COUNT(V.vote) AS total_vote, 
				MAX(CAST(SHA1(CONCAT('%s',CAST(V.rand_prop AS CHAR))) AS CHAR)=V.hash_prop) AS my_vote, 
				MAX(CAST(SHA1(CONCAT('%s',CAST(V.rand_prop AS CHAR))) AS CHAR)=V.hash_prop AND V.vote) AS my_provote
				FROM comment C, vote_comment V
				WHERE C.thread_id='%s' AND V.comment_id=C.comment_id
				GROUP BY C.comment_id,C.rand_prop,C.hash_prop,C.text,C.date,C.is_valid,C.already_mod,C.possibly_name)
				UNION
				(SELECT C.comment_id,C.rand_prop,C.hash_prop,C.text,C.date,C.is_valid,C.already_mod,C.possibly_name,
				0 AS pro_vote, 0 AS total_vote,0 AS my_vote, 0 AS my_provote
				FROM comment C
				WHERE C.thread_id='%s' AND C.comment_id<>ALL(SELECT comment_id FROM vote_comment))
				ORDER BY date ASC",$escaped_name,$escaped_name,$escaped_threadid,$escaped_threadid));
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
					if($row=mysql_fetch_assoc($result_temp))
					{
						$nb_comment = htmlentities($row["NB_COMMENT"]);
						display_speccom($unique_mode,$ancre,$thread_id,$nb_comment,'roll');
					}
					@mysql_free_result($result_temp);
					while($row=mysql_fetch_assoc($result))
					{
						$is_proprio=check_property($row["rand_prop"],$row["hash_prop"]);
						$is_valid=$row["is_valid"];
						$already_mod=$row["already_mod"];
						$comment_id=$row["comment_id"];

						$ancre=htmlentities($row["comment_id"]);
						$date=$row['date'];
						$possibly_name=$row['possibly_name'];
						$sec_cid=htmlentities($row["comment_id"]);
						$thread_tmp=htmlentities($row["thread_id"]);
						$text=text_display_prepare(trim($row["text"]));
						
						if ($is_valid || $is_proprio || $privileges>3)
						{

							// afficher les commentaires
							display_comment($is_proprio,$is_logged,$ancre,$date,$possibly_name,$row['my_vote'],$row['my_provote'],$row['total_vote'],$thread_tmp,$text,$privileges,$is_admin,$sec_cid);
							
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
						// display the form that allows users to post comments
						display_comment_form($unique_mode,$ancre,$thread_id,$text_prec,$anon_prec);
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
						$nb_comment = htmlentities($row["NB_COMMENT"]);
						display_speccom($unique_mode,$ancre,$thread_id,$nb_comment,'unroll');
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
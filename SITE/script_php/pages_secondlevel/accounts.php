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

function log_in()
{
	if (is_logged())
	{
		echo('<div class="warning">Vous &ecirc;tes d&eacute;j&agrave; logg&eacute;</div>');
	}
	else
	{	
		$affich_form=true;
		$login_clair="";

		if (isset($_SESSION['post']))
		{
			$_POST=$_SESSION['post'];
			unset($_SESSION['post']);
		}

		if (isset($_POST['form_name']) && $_POST['form_name']=="login")
		{
			$passw_clair="";
			
			$check_1=(isset($_POST["login"]) && is_string($_POST["login"]) && !empty($_POST["login"]));
			$check_2=(isset($_POST["password"]) && is_string($_POST["password"]) && !empty($_POST["password"]));
			
			if($check_1)
			{
				$login_clair=$_POST["login"];
			}
			if($check_2)
			{
				$passw_clair=$_POST["password"];
			}
			
			if ($check_1 && $check_2)
			{
				$hash_log=sha1($login_clair);
				$hash_pass=sha1($passw_clair);
				
				$result=@mysql_query(sprintf("SELECT user_id,is_valid,privileges FROM user WHERE hash_mail='%s' AND hash_pass='%s'",mysql_real_escape_string($hash_log),mysql_real_escape_string($hash_pass)));
				if (!$result)
				{
					echo('<div class="warning">Erreur lors de l\'authentification</div>');	
				}
				else
				{
					$compteur=0;
					$uid=0;
					$valid=0;
					$privileges=0;
					while ($row=mysql_fetch_assoc($result))
					{
						$compteur++;
						$uid=$row["user_id"];
						$privileges=$row["privileges"];
						$valid=$row["is_valid"];
					}	
					if ($compteur==1)
					{
						if ($valid==1)
						{
							// Il n'est pas idéal de conserver ces données en clair (cf. notamment vol de session, firesheep, ...)
							// Néanmoins indispensable pour le mécanisme d'anonymat permettant tout de même à l'auteur un contrôle sur ses posts et la proposition d'ajout de nom
							// En toute généralité, forcer le SSL serait une option intéressante
							$_SESSION['login_c']=$login_clair;
							$_SESSION['passw']=sha1($passw_clair);
							$_SESSION['uid']=$uid;
							if ($privileges==5)
							{
								$_SESSION['privileges']=5;
							}
							elseif ($privileges==4)
							{
								$_SESSION['privileges']=4;
							}
							else
							{
								$_SESSION['privileges']=3;
							}
							if(isset($_SESSION['transient_display']))
							{
								unset($_SESSION['transient_display']);
							}
							echo('<div class="success">Succ&egrave;s de l\'authentification</div>');
							$affich_form=false;
						}
						else
						{
							echo('<div class="warning">Compte non activ&eacute;</div>');	
						}
					}
					else
					{
						echo('<div class="warning">Couple login/mail inconnu</div>');	
					}
					@mysql_free_result($result);
				}
			}
			else
			{
				echo('<div class="warning">Donn&eacute;es invalides</div>');
			}
		}	
			
		if ($affich_form)
		{
			echo('

			<form method="post" action="?action=login" class="ym-form">
				<div class="ym-fbox-text">
				  <label for="login">'._('Login').((LIMIT_MAIL)?'('.PREGMATCH_MAIL_HUMAN_READABLE.')':'').' :</label>
				  <input type="text" name="login" id="login" size="20" value="'.htmlentities($login_clair).'" />
				</div>
				<div class="ym-fbox-text">
				  <label for="your-id">'._('Password').'</label>
				  <input type="password" name="password" id="password" size="20" />
				</div>
				  <input type="hidden" name="form_name" value="login" />
				  <input type="submit" value="'._('Login').'" />
			</form>

			');
		}
	}

	if (isset($_POST))
	{
		unset($_POST);
	}
}

function log_out($loc_appel=0)
{
	// Déconnexion effective
	if ($loc_appel==1)
	{
		if (isset($_SESSION))
		{
			unset($_SESSION);
		}
		@session_destroy();
	}
	// Affichage du message de déconnexion
	elseif ($loc_appel==0)
	{
		echo('<h1>D&eacute;connexion :</h1>');
		echo('<div class="success">Vous &ecirc;tes bien d&eacute;connect&eacute;</div>');
	}
}

// Fonctions mails à mettre en commun !
function mail_confirmation_subscription($mail,$hash_mail,$cconf)
{
	$nexp=NOM_ECOLE." Refresh";
	$email=MAIL_CONTACT;
	$subject="Confirmation de votre inscription à ".NOM_ECOLE." refresh";
	$header = "From: ". $nexp . " <" . $email . ">\r\n";

	$link_deb="http://".$_SERVER['HTTP_HOST']."/index.php?action=confirm_subscribe";
	//$link_deb="http://refresh.ecole.org/dev_loc2/index.php?action=confirm_subscribe";
	
	$mail_body = "Bonjour,\n\nMerci de cliquer sur le lien suivant pour valider votre inscription : $link_deb&mail=$hash_mail&cconf=$cconf\n\nCordialement,\n\nl'équipe ".NOM_ECOLE." Refresh";
	
	$handle_fic = fopen('mail.txt', 'w+');
	fputs($handle_fic, $subject);
	fputs($handle_fic, "\n\n\n");
	fputs($handle_fic, $mail_body);
	fclose($handle_fic);
	
	return(mail($mail, mb_convert_encoding($subject,"ASCII","ISO-8859-1"), mb_convert_encoding($mail_body,"ASCII","ISO-8859-1"), $header));
}

function mail_change_password($mail, $new_pass)
{
	$nexp=NOM_ECOLE." Refresh";
	$email=MAIL_CONTACT;
	$subject="Changement mot de passe ".NOM_ECOLE." refresh";
	$header = "From: ". $nexp . " <" . $email . ">\r\n";
	$pass_secure=htmlentities($new_pass);
	$mail_body = "Bonjour,\n\nVoici votre nouveau mot de passe : $pass_secure\nIl est recommande de le changer rapidement.\n\nCordialement,\n\nl'equipe ".NOM_ECOLE." Refresh";
	
	$handle_fic = fopen('mail.txt', 'w+');
	fputs($handle_fic, $subject);
	fputs($handle_fic, "\n\n\n");
	fputs($handle_fic, $mail_body);
	fclose($handle_fic);
	
	return(mail(htmlentities($mail), mb_convert_encoding($subject,"ASCII","ISO-8859-1"), mb_convert_encoding($mail_body,"ASCII","ISO-8859-1"), $header));
}

function create_account()
{
	echo('<h1>Cr&eacute;ation de compte :</h1>');

	if (is_logged())
	{
		echo('<div class="warning">Vous poss&eacute;dez un compte et &ecirc;tes logg&eacute;</div>');	
	}
	else
	{
		$affich_form=true;
		$mail="";

		if (isset($_SESSION['post']))
		{
			$_POST=$_SESSION['post'];
			unset($_SESSION['post']);
		}

		if (isset($_POST['form_name']) && $_POST['form_name']=="create_account")
		{
			$pass_1="";
			$pass_2="";
			
			$check_1=(isset($_POST["mail"]) && is_string($_POST["mail"]) && !empty($_POST["mail"]));
			$check_2=(isset($_POST["pass1"]) && is_string($_POST["pass1"]) && !empty($_POST["pass1"]));
			$check_3=(isset($_POST["pass2"]) && is_string($_POST["pass2"]) && !empty($_POST["pass2"]));
			$check_4=(isset($_POST["accgu"]) && $_POST["accgu"]=="on");
			
			if ($check_1)
			{
				$mail=$_POST["mail"];
			}
			
			if ($check_1 && $check_2 && $check_3)
			{
				if ($check_4)
				{

					$pass_1=$_POST["pass1"];
					$pass_2=$_POST["pass2"];
					
					if ($pass_1==$pass_2)
					{
						if (strlen($pass_1)>5)
						{
							if (is_ecole_mail($mail))
							{
								$hash_mail=sha1($mail);
								$hash_pass=sha1($pass_1);
								
								$result=@mysql_query(sprintf("SELECT user_id,is_valid FROM user WHERE hash_mail='%s'",mysql_real_escape_string($hash_mail)));
								if (!$result)
								{
									echo('<div class="warning">Erreur lors de la requ&ecirc;te</div>');
								}
								else
								{
									$situation=0;
									while ($row=mysql_fetch_assoc($result))
									{
										if($row["is_valid"])
										{
											$situation=2;
										}
										else
										{
											$situation=1;
											// Plus propre de tout virer, on ne sait pas ce qui s'est passé qui a forcé l'utilisateur à faire une deuxième demande
											if (is_numeric($row["user_id"]))
											{	
												@mysql_query('DELETE FROM `user` WHERE `user`.`user_id` = '.mysql_real_escape_string($row["user_id"]));	
											}
										}	
									}
									
									// Adresse mail inscrite et compte activé, on ne fait rien
									if ($situation==2)
									{
										echo('<div class="warning">Adresse mail d&eacute;j&agrave; inscrite et compte activ&eacute;</div>');
									}
									// Adresse mail à inscrire
									else
									{
										// Adresse mail inscrite, compte non activé
										if ($situation==1)
										{
											echo('<div class="warning">Pr&eacute;c&eacute;dente demande d\'inscription de cette adresse annul&eacute;e</div>');
										}
										
										$cconf=random_password(40);
										$hcconf=sha1($cconf);
										
										// Les hash n'ont pas besoin d'être échappés
										if (@mysql_query("INSERT INTO `user` (`user_id`,`hash_mail`,`hash_pass`,`hash_conf`,`inscription_date`,`privileges`,`is_valid`) VALUES (NULL, '$hash_mail', '$hash_pass', '$hcconf', CURRENT_TIMESTAMP, 3, 0)"))
										{
											mail_confirmation_subscription($mail,$hash_mail,$cconf);
											echo('<div class="success">Inscription faite avec succ&egrave;s, un mail de confirmation vous a &eacute;t&eacute; envoy&eacute;</div>');
											$affich_form=false;
										}
										else
										{
											echo('<div class="warning">Erreur lors de votre inscription</div>');
										}
									}
									@mysql_free_result($result);
								}
							}
							else
							{
								echo('<div class="warning">Mail d\'un type invalide</div>');
							}
						}
						else
						{
							echo('<div class="warning">Mot de passe trop court, longueur minimale de six caract&egrave;res</div>');
						}
					}
					else
					{
						echo('<div class="warning">Mots de passe saisis diff&eacute;rents</div>');
					}
				}
				else
				{
					echo('<div class="warning">Il est n&eacute;cessaire d\'approuver au pr&eacute;alable les <a href="index.php?action=display_useterms">conditions d\'utilisation</a></div>');
				}
			}
			else
			{
				echo('<div class="warning">Donn&eacute;es invalides</div>');
			}
		}	
			
		if ($affich_form)
		{
			echo('

			<form method="post" action="?action=create_account">
				<table class="tab_form">
					<tr>
						<td>
							Adresse mail :
						</td>
						<td>
							<input type="text" name="mail" value="'.htmlentities($mail).'" />
						</td>
					</tr>
					<tr>
						<td>
							Mot de passe :
						</td>
						<td>
							<input type="password" name="pass1" />
						</td>
					</tr>
					<tr>
						<td>
							Confirmer le mot de passe :
						</td>
						<td>
							<input type="password" name="pass2" />
						</td>
					</tr>
					<tr>
						<td>
							J\'ai lu et accepte les <a href="index.php?action=display_useterms">conditions d\'utilisation</a>
						</td>
						<td>
							<input type="checkbox" name="accgu" />
						</td>
					</tr>
					<tr>
						<td>
							<input type="hidden" name="form_name" value="create_account" />
						</td>
						<td>
						</td>
					</tr>
					<tr class="submit_center">
						<td colspan="2" rowspan="1">
							<input type="submit" value="Valider" />
						</td>
					</tr>
				</table>
			</form>

			');
			
			
		echo('

		<br /><br />
		<p>
			Une adresse mail ne vous est demand&eacute;e qu\'&agrave; des fins d\'authentification (unicit&eacute; des votes) et pour garantir l\'acc&egrave;s aux seuls &eacute;l&egrave;ves de l\'&eacute;cole. Ne sont conserv&eacute;s 
			que des hashs, et tout est fait pour garantir votre confidentialit&eacute;.
		</p>'.((LIMIT_MAIL)?'<ul>
				<li>L\'adresse mail fournie doit-&ecirc;tre une adresse '.PREGMATCH_MAIL_HUMAN_READABLE.'</li>
			</ul>':'').'

			


		');
			
		}
	}

	if (isset($_POST))
	{
		unset($_POST);
	}
}

function validate_account()
{
	echo('<h1>Validation de l\'inscription :</h1>');

	if (isset($_GET["mail"]) && is_string($_GET["mail"]) && isset($_GET["cconf"]) && is_string($_GET["cconf"]))
	{
		$hash_mail=$_GET["mail"];
		$hash_ccnf=sha1($_GET["cconf"]);
		$result=@mysql_query(sprintf("SELECT user_id FROM user WHERE hash_mail='%s' AND hash_conf='%s'",mysql_real_escape_string($hash_mail),mysql_real_escape_string($hash_ccnf)));
		
		if (!$result)
		{
			echo('<div class="warning">Erreur lors de la requ&ecirc;te</div>');
		}
		else
		{
			$compteur=0;
			while ($row=mysql_fetch_assoc($result))
			{
				$compteur++;
				$query=sprintf("UPDATE user SET is_valid=1, hash_conf=NULL WHERE user_id='%s'",mysql_real_escape_string($row["user_id"]));
				if (is_numeric($row["user_id"]) && @mysql_query($query))
				{
					echo('<div class="success">Inscription valid&eacute;e, vous pouvez d&eacute;sormais vous <a href="?action=login">connecter</a></div>');
				}
				else
				{
					echo('<div class="warning">Erreur lors de la validation</div>');
				}	
			}
			if ($compteur==0)
			{							
				echo('<div class="warning">Donn&eacute;es de validation incorrectes</div>');
			}
			@mysql_free_result($result);
		}
	}
	else
	{
		echo('<div class="warning">Donn&eacute;es de validation incorrectes</div>');
	}
}

function change_password($forgotten_passw=false)
{
	if ($forgotten_passw)
	{
		echo('<h1>R&eacute;cup&eacute;ration de mot de passe :</h1>');
	}
	else
	{
		echo('<h1>Changement de mot de passe :</h1>');
	}
	
	if (isset($_SESSION['post']))
	{
		$_POST=$_SESSION['post'];
		unset($_SESSION['post']);
	}
	
	$affich_form=true;
	$is_logged=is_logged();

	$default_value_mail="";
	$default_value_oldpass="";
	$default_value_newpass_1="";
	$default_value_newpass_2="";
	
	if (isset($_POST['form_name']))
	{
		if ($_POST['form_name']=="mdp_oubli" && $forgotten_passw)
		{
			if (isset($_POST["mail"]) && !empty($_POST["mail"])) // On ne vérifie pas le type "x.x@eleves.ecole.fr" pour permettre la gestion de comptes spéciaux (administration, ...)
			{
				$default_value_mail=strtolower($_POST['mail']);
				$new_pass=random_password(8);
				
				$query=sprintf("UPDATE user SET hash_pass='%s' WHERE hash_mail='%s'",sha1($new_pass),sha1($default_value_mail));
				$res_q=@mysql_query($query);
				
				if ($res_q)
				{
					$nb_enr=mysql_affected_rows();
					if ($nb_enr<1)
					{
						echo('<div class="warning">L\'adresse mail ne semble pas inscrite</div>');
					}
					else
					{
						$affich_form=false;
						if(mail_change_password($default_value_mail, $new_pass))
						{
							echo('<div class="success">Mot de passe r&eacute;initialis&eacute;. Un mail vous a &eacute;t&eacute; envoy&eacute;</div>');
						}
						else
						{
							echo('<div class="warning">Mot de passe r&eacute;initialis&eacute, mais &eacute;chec de l\'envoi du mail. Veuillez r&eacute;essayer plus tard</div>');
						}
					}
					@mysql_free_result($res_q);
				}
				else
				{
					echo('<div class="warning">Impossible de mettre &agrave; jour le mot de passe</div>');
				}
			}
			else
			{
				echo('<div class="warning">Adresse incorrecte</div>');
			}
		}
		elseif ($_POST['form_name']=="mdp_change" && !$forgotten_passw && $is_logged)
		{
			$treat=true;
			
			// On vérifie les données en base, pour se protéger contre tout problème lié à la sécurité de la session
			$expected_password="";
			$expected_mail="";
			$res=@mysql_query(sprintf("SELECT hash_pass, hash_mail FROM user WHERE user_id='%s'",mysql_real_escape_string($_SESSION['uid'])));
			if ($res && $row=mysql_fetch_assoc($res))
			{
				$expected_password=$row["hash_pass"];
				$expected_mail=$row["hash_mail"];
				@mysql_free_result($res);
			}
			else
			{
				$treat=false;
				echo('<div class="warning">Impossible de v&eacute;rifier l\'identit&eacute; en base</div>');
			}

			if ($treat)
			{
				if(isset($_POST["mail"]) && !empty($_POST["mail"]))
				{
					$default_value_mail=strtolower($_POST["mail"]);
					if (!($expected_mail==sha1($default_value_mail)))
					{
						$treat=false;
						echo('<div class="warning">L\'adresse mail ne correspond pas &agrave; celle avec laquelle vous vous &ecirc;tes logg&eacute;</div>');
					}
				}
				else
				{
					$treat=false;
					echo('<div class="warning">Erreur dans le traitement de l\'adresse</div>');
				}
				if(isset($_POST["actual_password"]) && !empty($_POST["actual_password"]))
				{
					$default_value_oldpass=$_POST["actual_password"];
					if (!($expected_password==sha1($default_value_oldpass)))
					{
						$treat=false;
						echo('<div class="warning">Le mot de passe ne correspond pas &agrave; celui avec laquel vous vous &ecirc;tes logg&eacute;</div>');
					}
				}
				else
				{
					$treat=false;
					echo('<div class="warning">Erreur dans le traitement de l\'ancien mot de passe</div>');
				}
				if(isset($_POST["new_pass_1"]) && !empty($_POST["new_pass_1"]))
				{
					$default_value_newpass_1=$_POST["new_pass_1"];
				}
				else
				{
					$treat=false;
					echo('<div class="warning">Erreur dans le traitement du nouveau mot de passe</div>');
				}
				if(isset($_POST["new_pass_2"]) && !empty($_POST["new_pass_2"]))
				{
					$default_value_newpass_2=$_POST["new_pass_2"];	
				}
				else
				{
					$treat=false;
					echo('<div class="warning">Nouveau mot de passe non confirm&eacute;</div>');
				}
				if(!($default_value_newpass_1==$default_value_newpass_2))
				{
					$treat=false;
					echo('<div class="warning">Nouveaux mots de passe saisis diff&eacute;rents</div>');
				}
				elseif(!empty($default_value_newpass_1)) //$default_value_newpass_1 a à ce stade la même longueur que $default_value_newpass_2
				{
					if(strlen($default_value_newpass_1)<6)
					{
						$treat=false;
						echo('<div class="warning">Les mots de passe doivent &ecirc;tre composés d\'au moins 6 caractères</div>');
					}
				}
			}
			
			if($treat)
			{
				$query=sprintf("UPDATE user SET hash_pass='%s' WHERE hash_mail='%s'",sha1($default_value_newpass_1),sha1($default_value_mail));
				$res_q=@mysql_query($query);

				if ($res_q)
				{
					$nb_enr=mysql_affected_rows();
					if ($nb_enr<1)
					{
						echo('<div class="warning">L\'adresse mail ne semble pas inscrite</div>');
					}
					else
					{
						$affich_form=false;
						echo('<div class="success">Mot de passe correctement mis &agrave; jour</div>');
					}
					@mysql_free_result($res_q);
				}
				else
				{
					echo('<div class="warning">Impossible de mettre &agrave; jour le mot de passe</div>');
				}
			}	
		}
		else
		{
			echo('<div class="warning">Erreur dans le traitement du formulaire</div>');
		}
	}

	if ($affich_form)
	{
		if ($forgotten_passw)
		{
			echo('
			
			<form method="post" action="?action=lost_ids">
				<table class="tab_form">
					<tr>
						<td>
							Adresse mail inscrite '.((LIMIT_MAIL)?'('.PREGMATCH_MAIL_HUMAN_READABLE.')':'').' :
						</td>
						<td>
							<input type="text" name="mail" value="'.htmlentities($default_value_mail).'" />
						</td>
					</tr>
					<tr>
						<td>
							<input type="hidden" name="form_name" value="mdp_oubli" />
						</td>
						<td>
						</td>
					</tr>
					<tr class="submit_center">
						<td colspan="2" rowspan="1">
							<input type="submit" value="Valider" />
						</td>
					</tr>
				</table>
			</form>
			
			');
		}
		elseif ($is_logged)
		{
			echo('
			
			<form method="post" action="?action=change_pass">
				<table class="tab_form">
					<tr>
						<td>
							Adresse mail (confirmation de l\'identit&eacute;) :
						</td>
						<td>
							<input type="text" name="mail" value="'.htmlentities($default_value_mail).'" />
						</td>
					</tr>
					<tr>
						<td>
							Mot de passe actuel (confirmation de l\'identit&eacute;) :
						</td>
						<td>
							<input type="password" name="actual_password" />
						</td>
					</tr>
					<tr>
						<td>
							Nouveau mot de passe :
						</td>
						<td>
							<input type="password" name="new_pass_1" />
						</td>
					</tr>
					<tr>
						<td>
							Confirmer le nouveau mot de passe :
						</td>
						<td>
							<input type="password" name="new_pass_2" />
						</td>
					</tr>
					<tr>
						<td>
							<input type="hidden" name="form_name" value="mdp_change" />
						</td>
						<td>
						</td>
					</tr>
					<tr class="submit_center">
						<td colspan="2" rowspan="1">
							<input type="submit" value="Valider" />
						</td>
					</tr>
				</table>
			</form>
			');
		}
		else
		{
			echo('<div class="warning">Impossible de choisir un nouveau mot de passe sans &ecirc;tre logg&eacute;</div>');
		}
	}

	if (isset($_POST))
	{
		unset($_POST);
	}
}

function delete_account()
{
	$retour="";
	
	$retour.='<h1>Suppression de compte :</h1>';
	
	if (is_logged())
	{
		$affich_form=true;

		$default_value_mail="";
		$default_value_oldpass="";

		if (isset($_POST['form_name']) && $_POST['form_name']=="delete_account")
		{
			$treat=true;
			$delete_conf=false;
			$delete_proposal=true;
			$delete_comments=true;
			$delete_votes=true;
		
			$expected_password="";
			$expected_mail="";
			$res=@mysql_query(sprintf("SELECT hash_pass, hash_mail FROM user WHERE user_id='%s'",mysql_real_escape_string($_SESSION['uid'])));
			if ($res && $row=mysql_fetch_assoc($res))
			{
				$expected_password=$row["hash_pass"];
				$expected_mail=$row["hash_mail"];
				@mysql_free_result($res);
			}
			else
			{
				$treat=false;
				$retour.='<div class="warning">Impossible de v&eacute;rifier l\'identit&eacute; en base</div>';
			}
			
			if ($treat) // Vérifications sur les arguments
			{
				if(isset($_POST["mail"]) && !empty($_POST["mail"]))
				{
					$default_value_mail=strtolower($_POST["mail"]);
					if (!($expected_mail==sha1($default_value_mail)))
					{
						$treat=false;
						$retour.='<div class="warning">L\'adresse mail ne correspond pas &agrave; celle avec laquelle vous vous &ecirc;tes logg&eacute;</div>';
					}
				}
				else
				{
					$treat=false;
					$retour.='<div class="warning">Erreur dans le traitement de l\'adresse</div>';
				}
				if(isset($_POST["actual_password"]) && !empty($_POST["actual_password"]))
				{
					$default_value_oldpass=$_POST["actual_password"];
					if (!($expected_password==sha1($default_value_oldpass)))
					{
						$treat=false;
						$retour.='<div class="warning">Le mot de passe ne correspond pas &agrave; celui avec laquel vous vous &ecirc;tes logg&eacute;</div>';
					}
				}
				else
				{
					$treat=false;
					$retour.='<div class="warning">Erreur dans le traitement du mot de passe</div>';
				}
				if(!isset($_POST["confirm_suppress"]))
				{
					$treat=false;
					$retour.='<div class="warning">Vous n\'avez pas confirm&eacute; la suppression de votre compte</div>';
				}
				elseif ($_POST["confirm_suppress"]=="on")
				{
					$delete_conf=true;
				}
				else
				{
					$treat=false;
					$retour.='<div class="warning">Erreur dans le traitement de la confirmation</div>';
				}
				if(!isset($_POST["prop_suppress"]))
				{	
					$delete_proposal=false;
				}
				elseif ($_POST["prop_suppress"]=="on")
				{
					$delete_proposal=true;
				}
				else
				{
					$treat=false;
					$retour.='<div class="warning">Erreur dans le traitement du formulaire</div>';
				}
				if(!isset($_POST["comment_suppress"]))
				{	
					$delete_comments=false;
				}
				elseif ($_POST["comment_suppress"]=="on")
				{
					$delete_comments=true;
				}
				else
				{
					$treat=false;
					$retour.='<div class="warning">Erreur dans le traitement du formulaire</div>';
				}
				if(!isset($_POST["vote_suppress"]))
				{	
					$delete_votes=false;
				}
				elseif ($_POST["vote_suppress"]=="on")
				{
					$delete_votes=true;
				}
				else
				{
					$treat=false;
					$retour.='<div class="warning">Erreur dans le traitement du formulaire</div>';
				}
			}

			if ($treat && $delete_conf) // Application des commandes
			{
				$query=sprintf("DELETE FROM `user` WHERE `user`.`user_id`='%s'",mysql_real_escape_string($_SESSION['uid']));
				if(@mysql_query($query)) // Lien adresse mail en cas d'erreur à passer en alias
				{
					$retour.='<div class="success">Compte correctement supprim&eacute</div>';
					$affich_form=false;
					
					if ($delete_proposal) // Les votes et commentaires associés sont normalement supprimés en cascade selon la contrainte sur la clé étrangère
					{
						$query=sprintf("DELETE FROM `thread` WHERE CAST(SHA1(CONCAT('%s',CAST(rand_prop AS CHAR))) AS CHAR)=hash_prop",mysql_real_escape_string($default_value_mail));
						if(@mysql_query($query))
						{
							$retour.='<div class="success">Propositions correctement supprim&eacute;es</div>';
						}
						else
						{
							$retour.='<div class="warning">Erreur lors de la suppression des propositions ; veuillez vous adresser au <a href="mailto:webmaster_ppr@ecole.org">webmaster</a></div>';
						}
					}
					if ($delete_comments)
					{
						$query=sprintf("DELETE FROM `comment` WHERE CAST(SHA1(CONCAT('%s',CAST(rand_prop AS CHAR))) AS CHAR)=hash_prop",mysql_real_escape_string($default_value_mail));
						if(@mysql_query($query))
						{
							$retour.='<div class="success">Commentaires correctement supprim&eacute;s</div>';
						}
						else
						{
							$retour.='<div class="warning">Erreur lors de la suppression des commentaires ; veuillez vous adresser au <a href="mailto:webmaster_ppr@ecole.org">webmaster</a></div>';
						}
					}
					if ($delete_votes)
					{
						$query=sprintf("DELETE FROM `vote` WHERE CAST(SHA1(CONCAT('%s',CAST(rand_prop AS CHAR))) AS CHAR)=hash_prop",mysql_real_escape_string($default_value_mail));
						if(@mysql_query($query))
						{
							$retour.='<div class="success">Votes correctement supprim&eacute;s</div>';
						}
						else
						{
							$retour.='<div class="warning">Erreur lors de la suppression des votes ; veuillez vous adresser au <a href="mailto:webmaster_ppr@ecole.org">webmaster</a></div>';
						}
					}
					
					unset($_SESSION['login_c']);
					unset($_SESSION['passw']);
					unset($_SESSION['uid']);
					unset($_SESSION['privileges']);
					$retour.='<div class="success">Vous &ecirc;tes bien d&eacute;connect&eacute;</div>';
				}
				else
				{
					$retour.='<div class="warning">Erreur lors de la suppression du compte; veuillez r&eacute;essayer plus tard</div>';
				}
			}
		}

		if ($affich_form)
		{
			$retour.='
			
			<form method="post" action="?action=delete_account">
				<table class="tab_form">
					<tr>
						<td>
							Adresse mail (confirmation de l\'identit&eacute;) :
						</td>
						<td>
							<input type="text" name="mail" value="'.htmlentities($default_value_mail).'" />
						</td>
					</tr>
					<tr>
						<td>
							Mot de passe (confirmation de l\'identit&eacute;) :
						</td>
						<td>
							<input type="password" name="actual_password" />
						</td>
					</tr>
					<tr>
						<td>
							Supprimer toutes mes propositions :
						</td>
						<td>
							<input type="checkbox" name="prop_suppress" checked="checked" />
						</td>
					</tr>
					<tr>
						<td>
							Supprimer tous mes commentaires :
						</td>
						<td>
							<input type="checkbox" name="comment_suppress" checked="checked" />
						</td>
					</tr>
					<tr>
						<td>
							Supprimer tous mes votes :
						</td>
						<td>
							<input type="checkbox" name="vote_suppress" checked="checked" />
						</td>
					</tr>
					<tr>
						<td>
							Oui, je souhaite bien supprimer mon compte :
						</td>
						<td>
							<input type="checkbox" name="confirm_suppress" />
						</td>
					</tr>
					<tr>
						<td>
							<input type="hidden" name="form_name" value="delete_account" />
						</td>
						<td>
						</td>
					</tr>
					<tr class="submit_center">
						<td colspan="2" rowspan="1">
							<input type="submit" value="Valider" />
						</td>
					</tr>
				</table>
			</form>

			';
		}
	}
	else
	{
		$retour.='<div class="warning">Vous devez &ecirc;tre logg&eacute; pour effectuer cette action</div>';
	}
	
	if (isset($_POST))
	{
		unset($_POST);
	}
	
	return($retour);
}

?>

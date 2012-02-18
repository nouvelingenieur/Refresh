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

session_start();

include_once("config.php");

/********************************************************************************************************************************/
// INITIALIAZING LOCALIZATION (LANGUAGES) - INITIALISATION DE LA LOCALISATION (LANGUES)
/********************************************************************************************************************************/

putenv("LC_ALL=".LANG);
setlocale(LC_ALL, LANG);
bindtextdomain("messages", "locale");
textdomain("messages");

/********************************************************************************************************************************/
// ADDING OTHER MODULES - AJOUT DES AUTRES MODULES
/********************************************************************************************************************************/


include_once("script_php/pages_secondlevel/documents.php");
include_once("script_php/pages_secondlevel/accounts.php");
include_once("script_php/pages_secondlevel/posts.php");
include_once("script_php/pages_secondlevel/comments.php");
include_once("script_php/pages_secondlevel/tool.php");

/********************************************************************************************************************************/
// Sont appelées ici toutes les fonctions qui ne sont exécutées que de manière "transitoire" avant un retour sur une autre page //
/********************************************************************************************************************************/
$treat_post=true;
if (isset($_GET["action"]) && is_string($_GET["action"]))
{
	$ccar_to_treat=htmlentities($_GET["action"]);
	switch ($ccar_to_treat)
	{
		case "logout":
			$treat_post=false;
			if (is_logged())
			{
				header('Location:index.php?action=logout');
				log_out(1);
			}
			break;
		case "moderation":
			$treat_post=false;
			moderate_post();
			header('Location:index.php?action=display_post');
			break;
		case "anonymization":
			$treat_post=false;
			change_post_confidentiality_status();
			header('Location:index.php?action=display_post');
			break;
		case "vote_post":
			$treat_post=false;
			vote_post();
			header('Location:index.php?action=display_post');
			break;
		case "accept_cgu":
			$treat_post=false;
			$_SESSION['confirmation_agreement']="ok";
			header('Location:index.php?action=display_docu');
			break;
		case "post_filter_change":
			$treat_post=false;
			modify_thread_display_filtering();
			header('Location:index.php?action=display_post');
			break;
		case "change_thread_page":
			$treat_post=false;
			modify_thread_display_page();
			header('Location:index.php?action=display_post');
			break;
		case "docs_filter_change":
			$treat_post=false;
			modify_docu_display_filtering();
			header('Location:index.php?action=display_docu');
			break;
		case "change_document_page":
			$treat_post=false;
			modify_docu_display_page();
			header('Location:index.php?action=display_docu');
			break;
		case "unrollcomment":
			$treat_post=false;
			if(isset($_GET["order"])) // Affichage des commentaires pour un thread donné
			{
				if ($_GET["order"]==1)
				{
					if(isset($_GET["thread_id"]) && is_numeric($_GET["thread_id"]))
					{
						$_SESSION["unroll_comment"]=$_GET["thread_id"];
					}
				}
				elseif($_GET["order"]==0)
				{
					unset($_SESSION["unroll_comment"]);
				}
			}
			// Nettoyage de restes de formulaires éventuels
			if (isset($_SESSION["text_new_comment_rest"]))
			{
				unset($_SESSION["text_new_comment_rest"]);
			}
			if (isset($_SESSION["text_anonymous_rest"]))
			{
				unset($_SESSION["text_anonymous_rest"]);
			}
			header('Location:index.php?action=display_post');
			break;
		// Nouveau commentaire
		case "comment_post":
			new_comment();
			$balise="";
			if (isset($_SERVER['QUERY_STRING']) && !empty($_SERVER['QUERY_STRING']))
			{
				$pos = strpos($_SERVER['QUERY_STRING'],"#");
				if ($pos !== false) 
				{
					$balise=substr($_SERVER['QUERY_STRING'],$pos+1);
				}
			}
			if (!empty($balise))
			{
				header('Location:index.php?action=display_post#'.$balise); // Maintien de la hauteur dans la page
			}
			else
			{
				header('Location:index.php?action=display_post');
			}
			break;
		case "delete_account": // Assez inélégant, mais nécessaire à l'actualisation totale de la page après une suppression réussie qui entraîne la déconnexion
			$treat_post=false;
			if(!isset($_SESSION["delete_account_state"])) // Cas vide, on arrive, il faut exécuter la fonction delete_account()
			{
				$_SESSION["delete_account_state"]="execute";
			}
			if($_SESSION["delete_account_state"]=="execute")
			{
				$_SESSION["delete_account_state"]="wait"; // On signale que la page est en attente d'actualisation
				$_SESSION["delete_account_display"]=delete_account(); // Exécution proprement dite
				header('Location:index.php?action=delete_account'); // Redirection vers l'affichage
			}
			elseif ($_SESSION["delete_account_state"]=="wait")
			{
				$_SESSION["delete_account_state"]="display"; // La page a été réactualisée, on peut maintenant afficher les résultats
			}
			break;
		case "new_document": // Similaire à "delete_account" : on ne peut se permettre une redirection *avant* le traitement, car le fichier temporaire uploadé est supprimé... 
			$treat_post=false;
			if(!isset($_SESSION["new_document_state"])) // On exécute dans le cas vide (typiquement, premier appel de la page)
			{
				$_SESSION["new_document_state"]="execute";
			}
			if($_SESSION["new_document_state"]=="execute")
			{
				$_SESSION["new_document_state"]="wait"; // La page est en attente de réactualisation
				$_SESSION["new_document_display"]=add_document(); // Exécution
				header('Location:index.php?action=new_document'); // Redirection vers l'affichage
			}
			elseif ($_SESSION["new_document_state"]=="wait") // La page a été actualisée
			{
				$_SESSION["new_document_state"]="display"; // On passe en mode "affichage"
			}
			break;
	}
}

// Gestion des problèmes d'actualisation avec $_POST (dans le cas où il n'y a pas déjà eu actualisation précédemment)
if(isset($_POST) && !(empty($_POST)) && $treat_post)
{
	$_SESSION['post'] = $_POST ; // Passage en session et élimination
	unset($_POST);
	
	if (isset($_SERVER['PHP_SELF'])) // Redirection vers l'adresse d'origine
    {
		$p_act = $_SERVER['PHP_SELF'] ;
        if (isset($_SERVER['QUERY_STRING']) && !empty($_SERVER['QUERY_STRING']))
        {
            $p_act.='?'.$_SERVER['QUERY_STRING'];
        }
	}
	
	if (isset($p_act))
	{
		header('Location:'.$p_act);
	}
	else
	{
		header('Location:index.php');
	}
    exit;
}

?>
<!--

	Plateforme web PPR - outil de crowdsourcing
	Copyright(C) 2011 Nicolas SEICHEPINE

    This program is free software: you can redistribute it and/or modify
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

-->

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="fr" >
	
	<head>
		<title><?=NOM_ECOLE?> Refresh</title>
		<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
		<meta http-equiv="Content-Style-Type" content="text/css" />
		<meta name="keywords" content="<?=KEYWORDS?>" />
		<link rel="stylesheet" type="text/css" href="feuille_style.css" />
	</head>

	<body>
	
		<div id="title_princ">
				<a href="?action=go_home"><img src="rep_img/logo_petit.png" alt="Logo" id="logo_p" height="107" /></a><br />
			<?=NOM_ECOLE?>
			<span id="title_princ_second_part">
				REFRESH
			</span>	
			<br />
			<span id="sub_title_princ">
				L'innovation en marche
			</span>
		</div>
	
		<table id="menu">
			<tr class="menu_margin">
				<td rowspan="1" colspan="5">
				</td>
			</tr>
			<tr>
				<?php include("./script_php/menu_principal.php"); ?>
			</tr>
			<tr class="menu_margin">
				<td rowspan="1" colspan="5">
				</td>
			</tr>
		</table>

		<div id="corps">
			<?php include("./script_php/corps.php"); ?>
		</div>
		
		<div id="account_handling">
			<?php include("./script_php/menu_compte.php") ?>
		</div>

		
	</body>

</html>

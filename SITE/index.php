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

<!doctype html>
<!--[if lt IE 7]> <html class="no-js lt-ie9 lt-ie8 lt-ie7" lang="en"> <![endif]-->
<!--[if IE 7]>    <html class="no-js lt-ie9 lt-ie8" lang="en"> <![endif]-->
<!--[if IE 8]>    <html class="no-js lt-ie9" lang="en"> <![endif]-->
<!--[if gt IE 8]><!--> <html class="no-js" lang="en"> <!--<![endif]-->
<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
	<meta name="description" content="">
	<meta name="author" content="">
	<meta name="keywords" content="<?=KEYWORDS?>" />


	<title><?=NOM_ECOLE?> Refresh</title>

	<!-- Mobile viewport optimisation -->
	<meta name="viewport" content="width=device-width, initial-scale=1.0">

	<!--[if lte IE 7]>
	<link href="../yaml/core/iehacks.css" rel="stylesheet" type="text/css" />
	<![endif]-->

	<!--[if lt IE 9]>
	<script src="http://html5shim.googlecode.com/svn/trunk/html5.js"></script>
	<![endif]-->
	<link rel="stylesheet" href="css/style.css">

	<script src="js/libs/modernizr-2.5.2.min.js"></script>

<body>
<!--[if lt IE 7]><p class=chromeframe>Your browser is <em>ancient!</em> <a href="http://browsehappy.com/">Upgrade to a different browser</a> or <a href="http://www.google.com/chromeframe/?redirect=true">install Google Chrome Frame</a> to experience this site.</p><![endif]-->
	
<ul class="ym-skiplinks">
	<li><a class="ym-skip" href="#nav">Skip to navigation (Press Enter)</a></li>
	<li><a class="ym-skip" href="#main">Skip to main content (Press Enter)</a></li>
</ul>
<div class="ym-wrapper">

	<nav id="menu">
		<div class="ym-hlist" >
			<ul>
			<?php include("./script_php/menu_principal.php"); ?>
			</ul>
		
			<form class="ym-searchform">
				<input class="ym-searchfield" type="search" placeholder="Search..." />
				<input class="ym-searchbutton" type="submit" value="Search" />
			</form>
		</div>
	</nav>
	
<div id="main">
	<div class="ym-grid">
		<div class="ym-g75 ym-gl content">
		  <div class="ym-gbox">
			<?php include("./script_php/corps.php"); ?>
		  </div>
		</div>
		<aside class="ym-g25 ym-gr">
		  <div class="ym-gbox">
			<div class="box info">
			<?php include("./script_php/menu_compte.php") ?>
			</div>
			<div class="info">
				<div class="box widget">
					<h4 class="widget-title"><?php echo _('Latest posts')?></h4>
					<ul>
						<li>Item 1</li>
						<li>Item 2</li>
						<li>Item 3</li>
						<li>Item 4</li>
						<li>Item 5</li>
					</ul>
				</div>
				<div class="box widget">
					<h4 class="box widget-title"><?php echo _('Medias')?></h4>
					<ul>
						<li>Item 1</li>
						<li>Item 2</li>
						<li>Item 3</li>
					</ul>
				</div>
				<div class="box widget">
					<h4 class="widget-title"><?php echo _('Most readed')?></h4>
					<ul>
						<li>Item 1</li>
						<li>Item 2</li>
						<li>Item 3</li>
					</ul>
				</div>
				<div class="box widget">
					<h4 class="widget-title"><?php echo _('Some links')?></h4>
					<ul>
						<li>Item 1</li>
						<li>Item 2</li>
						<li>Item 3</li>
					</ul>
				</div>
			</div>
		  </div>
		</aside>
	</div>
</div>
<footer class="bottom ym-clearfix">
   <nav>
	<div class="ym-hlist">
	   <ul>
		
		<li class="menu_title_selected_first">
			<a href="?action=go_home">Accueil</a>
		</li>
		<li class="menu_title">
			<a href="?action=display_nouvelingenieur">Le Nouvel Ingénieur</a>
		</li>
		<li class="menu_title">
			<a href="#">FAQ</a>
		</li>
		<li class="menu_title">
			<a href="#">Term of uses</a>
		</li>
		<li class="menu_title">
			<a href="#">Blog</a>
		</li>
	
	   </ul>
	</div>
   </nav>
   <section class="sns-links">
     <a href="#"> <img src="img/sns/twitter.png" title="Twitter" alt="Twitter"/> </a>
     <a href="#"> <img src="img/sns/facebook.png" title="Twitter" alt="Twitter"/> </a>
     <a href="#"> <img src="img/sns/rss.png" title="Twitter" alt="Twitter"/> </a>
     <a href="#"> <img src="img/sns/email.png" title="Twitter" alt="Twitter"/> </a>
   </section>
</footer>
<p class="license"><small>Copyright (c) le nouvel ingénieur</small></p>
</div><!-- end ym-wrapper -->

<!-- some scripts -->
<script src="//ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js"></script>
<script>window.jQuery || document.write('<script src="js/libs/jquery-1.7.1.min.js"><\/script>')</script>

<script src="js/plugins.js"></script>
<script src="js/script.js"></script>
<script>
	var _gaq=[['_setAccount','UA-XXXXX-X'],['_trackPageview']];
	(function(d,t){var g=d.createElement(t),s=d.getElementsByTagName(t)[0];
	g.src=('https:'==location.protocol?'//ssl':'//www')+'.google-analytics.com/ga.js';
	s.parentNode.insertBefore(g,s)}(document,'script'));
</script>


</body>
</html>

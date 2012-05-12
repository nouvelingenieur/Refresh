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
	
	Contact : nicolas.seichepine.org/?action=contact
*/

// Sert de couche d'analyse d'erreur et d'affichage dans l'affichage des documents

session_start();

include_once("config.php");
include_once("script_php/pages_secondlevel/tool.php");

$corps='';
$content='<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="fr" >
	<head>
		<title>'.NOM_ECOLE.' Refresh</title>
		<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
		<meta http-equiv="Content-Style-Type" content="text/css" />
		<meta name="keywords" content="'.KEYWORDS.'" />
		<link rel="stylesheet" type="text/css" href="feuille_style.css" />';

$success=false;
$name="";

if (user_privilege_level()>0) // Pour afficher le document, l'utilisateur doit être loggé ou se connecter depuis l'ecole et avoir accepté les CGU
{
	if(is_logged() || (isset($_SESSION['confirmation_agreement']) && $_SESSION['confirmation_agreement']=="ok"))
	{
		if ((isset($_GET["document_id"]) && is_numeric($_GET["document_id"])))
		{
			$result=@mysql_query(sprintf("SELECT filename FROM document WHERE document_id='%s'",mysql_real_escape_string($_GET["document_id"])));
			if ($result)
			{
				if ($row=mysql_fetch_assoc($result))
				{
					$name=htmlentities($row["filename"]); // htmlentities pour être sur d'avoir le même nom que celui utilisé dans pdf_gate.php
					$name="rep_documents/$name";
					if (file_exists($name))
					{
						$success=true;
					}
					else
					{
						$corps='<div class="warning">Fichier d&eacute;plac&eacute; ou supprim&eacute;</div>';
					}
				}
				else
				{
					$corps='<div class="warning">Aucun document correspondant</div>';
				}
				@mysql_free_result($result);
			}
			else
			{
				$corps='<div class="warning">Erreur lors de la requ&ecirc;te</div>';
			}
		}
		else
		{
			$corps='<div class="warning">Demande d\'acc&egrave;s incorrecte</div>';
		}
	}
	else
	{
		$corps='<div class="warning">Il est n&eacute;cessaire d\'approuver au pr&eacute;alable les <a href="index.php?action=display_useterms&amp;allow_direct_accept=true">conditions d\'utilisation</a></div>';
	}
}
else
{
	$corps='<div class="warning">Vous n\'avez pas le droit d\'acc&eacute;der &agrave; cette page</div>';
}

if ($success) // On ajoute le javascript de lecture; NB : le <noscript> dans <head> n'est pas valide XHTML 1.0 strict...
{
	$content.='
			<script type="text/javascript" src="./lect_flash/swfobject.js"></script>
			<script type="text/javascript"> 
				var swfVersionStr = "9.0.124";
				var xiSwfUrlStr="";
				var flashvars = { '.
					  'SwfFile : escape("http://'.$_SERVER['HTTP_HOST'].'/pdf_gate.php?document_id='.htmlentities($_GET["document_id"]).'"),'.
					  'Scale : 0.6, 
					  ZoomTransition : "easeOut",
					  ZoomTime : 0.5,
					  ZoomInterval : 0.1,
					  FitPageOnLoad : false,
					  FitWidthOnLoad : true,
					  PrintEnabled : false,
					  FullScreenAsMaxWindow : false,
					  ProgressiveLoading : true,
					  localeChain: "fr_FR"
					  };
				
				var params = {};
				
				params.quality = "high";
				params.bgcolor = "#ffffff";
				params.allowscriptaccess = "sameDomain";
				params.allowfullscreen = "true";
				
				var attributes = {};
				
				attributes.id = "FlexPaperViewer";
				attributes.name = "FlexPaperViewer";
				
				swfobject.embedSWF(
					"./lect_flash/FlexPaperViewer.swf", "flashContent", 
					"1000", "570", 
					swfVersionStr, xiSwfUrlStr, 
					flashvars, params, attributes);
				
				swfobject.createCSS("#flashContent", "display:block;text-align:left;");
				
			</script> 
			<noscript>
				<div class="warning">L\'activation du javascript est n&eacute;cessaire pour afficher les documents...</div>
				<p>
					<object type="text/html" data="trigger.php" width="0px" height="0px"></object>
				</p>
			</noscript> 
		</head>	
		<body> 
			<div style="position:absolute;top:5px;left:50%;margin-left:-500px;">
				<div id="flashContent"> 
					Flash Player version 9.0.124 ou post&eacute;rieure est n&eacute;cessaire...
				</div>
			</div>';
		
	// On signale que l'on est passé par la bonne page, de sorte que le javascript a le droit d'utiliser l'URL pdf_gate.php?document_id=...
	$_SESSION['hidden_authorize_pdf_gate_use']=time().'_3ec25e04a301535b9bc8c44b172dbcee420cf544';
}
else // Affichage générique
{
	$content.='</head>
	<body>
		<div id="corps">
			<h1>Affichage d\'un document :</h1>'.$corps.'
		</div>';
}

echo($content.'</body></html>');

?>

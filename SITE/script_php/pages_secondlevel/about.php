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

function about_nouvelingenieur()
{
	echo('

	<h1><img src="rep_img/nouvelingenieur.jpg" alt="Nouvel Ing&eacute;nieur" /></h1>

	<h2>Nouvel Ing&eacute;nieur</h2>

	<p>
		<a href="http://www.nouvelingenieur.fr/" target="_blank">Le Nouvel Ing&eacute;nieur</a>, partenaire de Refresh, 
		est un mouvement national &eacute;tudiant lanc&eacute; d&eacute;but 2011 par deux &eacute;l&egrave;ves-ing&eacute;nieurs ENAC. Il vise &agrave; adapter 
		et faire &eacute;voluer la formation d\'ing&eacute;nieur, notamment gr&acirc;ce aux contributions des &eacute;l&egrave;ves en &eacute;coles.
		L\'association, qui compte d&eacute;sormais plusieurs dizaines d\'&eacute;tudiants, dont quelques Ponts, a lanc&eacute; une r&eacute;flexion &agrave; 
		l\'&eacute;chelle nationale et cherche maintenant &agrave; s\'&eacute;tendre &agrave; de nouvelles &eacute;coles d\'ing&eacute;nieurs. Mais 
		avant d\'&ecirc;tre une association, le Nouvel Ing&eacute;nieur est un &eacute;tat d\'esprit : rejoignez la 
		<a href="http://www.facebook.com/LeNouvelIngenieur" target="_blank">page facebook</a>, faites avancer
		<a href="http://www.google.com/moderator/#16/e=4cc65" target="_blank">le d&eacute;bat</a> ou consultez 
		<a href="http://fr.wikibooks.org/wiki/CASES" target="_blank">le projet p&eacute;dagogique CASES</a>... Et participez vous aussi au changement &agrave; l\'&Eacute;cole sur Ponts ParisTech Refresh.
	</p>

	');
}

function about_ppr()
{

	$to_print='

	<h1>Bienvenue sur '.NOM_ECOLE.' Refresh</h1>

	<p>
		Ce site a pour but de se faire la voix des &eacute;l&egrave;ves aupr&egrave;s de l\'administration, afin d\'accompagner le changement dans l\'&Eacute;ducation.
	</p>

	<p>
		Amen&eacute;s &agrave; occuper des postes &agrave; responsabilit&eacute; au c&oelig;ur d\'un monde &eacute;conomique en mouvement, les &eacute;l&egrave;ves sont force de proposition et cela commence par leur scolarit&eacute; 
		et la vie pendant leurs &eacutetudes !
	</p>

	<p>
		Le code de la plateforme est libre et disponible <a href="https://github.com/nouvelingenieur/Refresh">ici</a>.
	</p>';

	echo($to_print);

}

function display_userterms()
{
	echo('<h1>Conditions d\'utilisation :</h1>
		<p>
En m\'inscrivant &agrave; ce service gratuit fourni pas les &eacute;l&egrave;ves, je m\'engage :
</p>
<ul>
<li>A consid&eacute;rer cette plateforme d\'&eacute;change comme interne &agrave; l\'&eacute;cole,
et par cons&eacute;quent &agrave; ne pas faire mention de tout ou partie des d&eacute;bats,
id&eacute;es, votes et document pr&eacute;sents sur la plateforme &agrave; toute personne, hors
&eacute;l&egrave;ves, enseignants, chercheurs et personnel administratif de l\'&eacute;cole;</li>

<li>A ne pas reproduire (t&eacute;l&eacute;chargement, impression, copie d\'&eacute;cran,
copie manuelle...) tout ou partie des documents disponibles en consultation
sur la plateforme, et &agrave; ne pas en divulguer le contenu ;</li>

<li>A garder en toute occasion une attitude constructive, digne et
respectueuse lors des &eacute;changes sur cette plateforme, et en particulier &agrave; ne
jamais prof&eacute;rer d\'insultes, d\'incitations &agrave; la haine ou &agrave; la discorde, ou &agrave;
formuler des critiques gratuites sans argument ni proposition ;</li>

<li>A ne jamais cibler ou mentionner dans une proposition ou un
commentaire une personne interne ou externe &agrave; l\'&eacute;cole par son nom, son
pr&eacute;nom ou sa fonction, si ce n\'est pour faire &eacute;tat d\'une bonne pratique
existante ;</li>

<li>A signaler tout comportement ne respectant pas les pr&eacute;sentes
conditions.</li>
</ul>
<p>
Tout non-respect de cette charte entra&icirc;nera une radiation imm&eacute;diate de la
plateforme, et sera passible de sanctions disciplinaires.

Il est rappel&eacute; aux &eacute;l&egrave;ves de l\'&eacute;cole que cette plateforme est g&eacute;r&eacute;e par les
&eacute;l&egrave;ves pour les &eacute;l&egrave;ves, et qu\'ils sont les seuls &agrave; y avoir acc&egrave;s. Les
pr&eacute;sentes conditions correspondent aux r&egrave;gles de biens&eacute;ance n&eacute;cessaires &agrave; un
d&eacute;bat serein et constructif et au maintien de l\'int&eacute;grit&eacute; morale et de la
r&eacute;putation des personnes concern&eacute;es, tant &eacute;l&egrave;ves qu\'enseignants, chercheurs
ou personnels administratifs.</p>
<p>

L\'&eacute;quipe Refresh

		</p>');
		
	if (isset($_GET['allow_direct_accept']) && $_GET['allow_direct_accept']="true")
	{
		echo('<a href="?action=accept_cgu">En cliquant sur ce lien, j\'accepte les conditions susmentionn&eacute;es</a>');
	}
}

?>
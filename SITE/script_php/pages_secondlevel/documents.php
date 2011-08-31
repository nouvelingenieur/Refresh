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

function modify_docu_display_filtering() // Donn�es non v�rifi�es, elles le sont � l'utilisation
{
	if (isset($_POST['form_name']) && $_POST['form_name']=="document_display_param")
	{
		if(isset($_POST["category_filter"])) // Pas de raison de prot�ger � ce niveau, les valeurs ne sont pas utilis�es ici et sont recopi�es directement 
		{
			$_SESSION["document_category_filter"]=$_POST["category_filter"];
		}
		if(isset($_POST["docsearch"]))
		{
			$_SESSION["document_search"]=$_POST["docsearch"];
		}
		if(isset($_POST["sorting"]))
		{
			$_SESSION["documents_ordering"]=$_POST["sorting"];
		}
	}
	unset($_POST);
}

function modify_docu_display_page() // Donn�es non v�rifi�es, elles le sont � l'utilisation
{
	if (isset($_GET["num_page"]))
	{
		$_SESSION["document_page"]=$_GET["num_page"];
	}
}

function display_documents()
{
	$rights=user_privilege_level();
	if ($rights>0) // Les personnes sans droits ne peuvent afficher les documents
	{
		echo('<h1>Documentation :</h1>');
		if(is_logged() || (isset($_SESSION['confirmation_agreement']) && $_SESSION['confirmation_agreement']=="ok")) // Il faut �tre logg� (donc avoir accept� les CGU une bonne fois pour toute) o� les avoir temporairement approuv�es pour pouvoir afficher la liste des documents
		{
			if(isset($_SESSION['transient_display']))
			{
				echo($_SESSION['transient_display']);
				unset($_SESSION['transient_display']);
			}
		
			// Formulaire de tri/filtrage des documents
			echo('<div class="enlarge_lowresol"><form method="post" action="?action=docs_filter_change">
			<table class="tab_form">
			<tr>
				<td>
					Cat&eacute;gorie :
				</td>
				<td>
					<select name="category_filter">');
			
			$tail="<option value=\"0\">Toutes</option>";
			$result=@mysql_query("SELECT category_id,category_name FROM document_category");
			if ($result)
			{
				while($row=@mysql_fetch_assoc($result))
				{
					// Autant se pr�munir contre une corruption des donn�es en base, �a ne co�te rien (sauf du CPU ^^)
					if (isset($_SESSION["document_category_filter"]) && $row["category_id"]==$_SESSION["document_category_filter"])
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
			
			echo($tail.'</select></td>
					<td>
						Rechercher :
					</td>
					<td>');
	
			if (isset($_SESSION["document_search"]) && !empty($_SESSION["document_search"]))
			{
				echo('<input type="text" name="docsearch" value="'.htmlentities($_SESSION["document_search"]).'" />');
			}
			else
			{
				echo('<input type="text" name="docsearch" />');
			}
			echo('</td>
					<td>
						Trier par :
					</td>
					<td>
						<select name="sorting">');	
			
			$tail="";			
			if (isset($_SESSION["documents_ordering"]))
			{
				$thread_ordering=$_SESSION["documents_ordering"];
				if($thread_ordering==1)
				{
					$tail="<option value=\"1\" selected=\"selected\">Date</option><option value=\"2\">Pertinence</option>";
				}
				elseif($thread_ordering==2)
				{
					$tail="<option value=\"1\">Date</option><option value=\"2\" selected=\"selected\">Pertinence</option>";
				}
			}
			else
			{
				$tail="<option value=\"1\">Date</option><option value=\"2\">Pertinence</option>";
			}
	
			echo($tail.'</select></td>
				<td><input type="hidden" name="form_name" value="document_display_param" /></td>
				<td><input type="submit" value="Valider" /></td>
				</tr>
			</table></form></div><br />');

			// Construction de la recherche
			$need_to_search=false;
			$string_search="";
			if (isset($_SESSION["document_search"]) && !empty($_SESSION["document_search"]))
			{
				$need_to_search=true;
				$string_search=mysql_real_escape_string($_SESSION["document_search"]);
				$query="SELECT D.document_id, D.name, D.description, D.filedate, C.category_name, MATCH(D.description) AGAINST ('$string_search' IN BOOLEAN MODE) AS score
					FROM document D, document_category C 
					WHERE D.category=C.category_id";
				$query_count="SELECT COUNT(*) AS NUM_RES 
					FROM document 
					WHERE MATCH(description) AGAINST ('$string_search')";
			}
			else
			{
				$query="SELECT D.document_id,D.name,D.description,D.filedate,C.category_name FROM document D, document_category C 
					WHERE D.category=C.category_id";
				$query_count="SELECT COUNT(*) AS NUM_RES FROM document";
			}
			if (isset($_SESSION["document_category_filter"]) && $_SESSION["document_category_filter"]>0)
			{
				$id_categ=mysql_real_escape_string($_SESSION["document_category_filter"]);
				$query.=" AND C.category_id=$id_categ";
				if ($need_to_search)
				{
					$query_count.=" AND category=$id_categ";
				}
				else
				{
					$query_count.=" WHERE category=$id_categ";
				}
			}
			
			$res=@mysql_query($query_count);
			$num_res=-1;
			if ($res && $num_res=@mysql_fetch_assoc($res))
			{
				$num_res=$num_res["NUM_RES"];
				@mysql_free_result($res);
			}
			
			$mandatory_post_tri=false;
			if ($need_to_search)
			{
				$query.=" AND MATCH(D.description) AGAINST ('$string_search' IN BOOLEAN MODE)";
			}
			if ($need_to_search && $_SESSION["documents_ordering"]==2)
			{
				$query.=" ORDER BY score DESC";
			}
			else
			{
				$query.=" ORDER BY filedate DESC";
			}
			if ($num_res>-1) // On a pu identifier sans probl�me le nombre de r�sultats potentiels
			{
				if(!isset($_SESSION["document_page"]) || !is_numeric($_SESSION["document_page"])) // Par d�faut, on va toujours � la page 1
				{
					$_SESSION["document_page"]=1;
				}
				if(!($_SESSION["document_page"]>0))
				{
					$_SESSION["document_page"]=1; 
				}
				$offset=round(10*($_SESSION["document_page"]-1));
				if ($offset>=$num_res) // En cas de p�pin, on retourne toujours � la page 1
				{
					$offset=0;
					$_SESSION["document_page"]=1;
				}
				$query.=" LIMIT $offset,10";
			}
			else // On ne limite pas
			{
				$_SESSION["document_page"]=1;
			}	
			
			$result=@mysql_query($query); //	Ex�cution de la requ�te de recherche des documents proprement dite
			if ($result)
			{
				$compteur=0;
				while ($row=mysql_fetch_assoc($result))
				{
					if ($compteur>0)
					{
						echo('<div class="newsterminator">
								<hr />
						</div>');
					}
					$doc_id=htmlentities($row["document_id"]);
					$name=htmlentities(stripslashes($row["name"]));
					$description=nl2br(htmlentities(stripslashes($row["description"])));
					$date=htmlentities(transfo_date($row["filedate"]));
					$category=htmlentities($row["category_name"]);
					
					echo('
					<div class="newstitle">
						<a href="pdf_display.php?document_id='.$doc_id.'">'.$name.'</a>
					</div>
					<div class="newsundertitle">
						'.$date.'&nbsp;-&nbsp;'.$category.'
					</div>
					<div class="newscontent">
						'.$description.'
					</div>');
					if ($rights>3) // Les administrateurs - mod�rateurs peuvent �diter et supprimer les documents
					{
						echo('<div class="newsendlinks">
							<a href="?action=edit_doc&amp;document_id='.$doc_id.'">Editer</a>
							<a href="?action=remove_doc&amp;document_id='.$doc_id.'">Supprimer</a>
						</div>');
					}
					$compteur++;
				}	
				if ($compteur==0)
				{
					echo('<div class="warning">Aucun document correspondant aux crit&egrave;res fix&eacute;s n\'est disponible pour le moment</div>');
				}
				elseif ($num_res>10)
				{
					echo('<div class="bottom_page_choice">');
					for ($i=1;$i<ceil($num_res/10)+1;$i++)
					{
						if($i==$_SESSION["document_page"])
						{
							echo("$i&nbsp;&nbsp;");
						}
						else
						{
							echo('<a href="?action=change_document_page&amp;num_page='.$i.'">'.$i.'</a>&nbsp;&nbsp;');
						}
					}
					echo('</div>');
				}
				@mysql_free_result($result);
			}
			else
			{
				echo('<div class="warning">Erreur lors du chargement</div>');	
			}
		}
		else
		{
			echo('<div class="warning">Il est n&eacute;cessaire d\'approuver au pr&eacute;alable les <a href="index.php?action=display_useterms&amp;allow_direct_accept=true">conditions d\'utilisation</a></div>');
		}
	}
	else
	{
		need_enpc_member_privilege(1);
	}
}

function add_document()
{
	$retour="<h1>Ajout de document :</h1>";
	
	if (user_privilege_level()>3) // Administrateurs - mod�rateurs
	{
		$affich_form=true;
		
		$default_title="";
		$default_categ=-1;
		$default_description="";
		
		if(isset($_POST['form_name']) && $_POST['form_name']=="new_document")
		{
			$treat=true;
			// On se passe de ['type'] qui est aussi bas� sur l'extension. D'autant que le type MIME renvoy� par le navigateur peut subir des variations ( PDF vs. File)
			if (isset($_FILES['uploaded_file']['name']) && isset($_FILES['uploaded_file']['size']) && is_numeric($_FILES['uploaded_file']['size']) && isset($_FILES['uploaded_file']['tmp_name']) && file_exists($_FILES['uploaded_file']['tmp_name']) && isset($_FILES['uploaded_file']['error']))
			{
				if (!(strtolower(strrchr($_FILES['uploaded_file']['name'],'.'))=='.pdf'))
				{
					$treat=false;
					$retour.='<div class="warning">Seuls les fichiers PDF sont autoris&eacute;s</div>';
				}
				if ($treat && $_FILES['uploaded_file']['size']>6291456)
				{
					$treat=false;
					$retour.='<div class="warning">Fichier trop lourd (> 6 Mo)</div>';
				}
				if ($treat && $_FILES['uploaded_file']['error'] > 0)
				{
					$treat=false;
					$retour.='<div class="warning">Erreur lors du transfert du fichier</div>';
				}			
			}
			else
			{
				$treat=false;
				$retour.='<div class="warning">Erreur lors de la transmission du formulaire associ&eacute; au fichier</div>';
			}

			if(isset($_POST["titre"]) && !empty($_POST["titre"]))
			{
				$default_title=$_POST["titre"];
			}
			else
			{
				$treat=false;
				$retour.='<div class="warning">Erreur dans le traitement du titre</div>';
			}
			if(isset($_POST["description"]) && !empty($_POST["description"]))
			{
				$default_description=$_POST["description"];
			}
			else
			{
				$treat=false;
				$retour.='<div class="warning">Erreur dans le traitement de la description</div>';
			}
			if (isset($_POST["category"]) && is_numeric($_POST["category"]))
			{
				if ($_POST["category"]>0)
				{
					$default_categ=$_POST["category"];
				}
				else
				{
					$treat=false;
					$retour.='<div class="warning">Cat&eacute;gorie invalide</div>';
				}
			}
			else
			{
				$treat=false;
				$retour.='<div class="warning">Erreur dans le traitement de la cat&eacute;gorie</div>';
			}

			if ($treat) // Int�gration du fichier
			{
				$name_newfile=sha1(uniqid('f').random_password(13)).'.swf'; // G�n�ration d'un nom qui soit unique (statistiquement) et non devinable		
				$commande='".\lect_flash\pdf2swf.exe" '.escapeshellarg($_FILES['uploaded_file']['tmp_name']).' ".\rep_documents\\'.$name_newfile.'" -T 9 -f';
				@exec($commande,$vinu,$res_comm);
				if ($res_comm!=0) // Conversion du pdf upload� en flash stock�
				{
					$retour.='<div class="warning">Impossible de r&eacute;cup&eacute;rer ou convertir le fichier</div>';
				}
				else
				{
					$query=sprintf("INSERT INTO `document` (`document_id`,`filename`,`name`,`description`,`filedate`,`category`) VALUES(NULL,'$name_newfile','%s','%s',CURRENT_TIMESTAMP,'%s')",mysql_real_escape_string($default_title),mysql_real_escape_string($default_description),mysql_real_escape_string($default_categ));  // Risque concernant un CATEGORY_ID erron� : au pire, non affichable; de toute fa�on, manipulation du formulaire d'upload exclue car r�serv� admin
					if (@mysql_query($query))
					{
						$affich_form=false;
						$retour.='<div class="success">Document correctement ajout&eacute;</div>';
					}
					else
					{
						$retour.='<div class="success">Erreur lors de l\'ajout de la description du document en base</div>';
					}
				}
			}
		}
			
		if($affich_form)
		{
			$retour.='
			<div class="enlarge_lowresol">
			<form method="post" action="?action=new_document" enctype="multipart/form-data">
				<table class="tab_form">
					<tr>
						<td>
							Titre :
						</td>
						<td>
							<input type="text" name="titre" value="'.htmlentities($default_title).'" />
						</td>
					</tr>
					<tr>
						<td>
							Cat&eacute;gorie :
						</td>
						<td>
							<select name="category">';	
						
							$tail="";
							$result=@mysql_query("SELECT category_id,category_name FROM document_category");
							if ($result)
							{
								while($row=mysql_fetch_assoc($result))
								{
									if ($default_categ==$row["category_id"])
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

					$retour.=$tail.'</select>
						</td>
					</tr>
					<tr>
						<td>
							Fichier :
						</td>
						<td>
							<input type="hidden" name="MAX_FILE_SIZE" value="6291456" />
							<input type="file" name="uploaded_file" />
						</td>
					</tr>
					<tr>
						<td>
							Description :
						</td>
						<td>
							<textarea name="description" rows="10" cols="50">'.htmlentities($default_description).'</textarea>
						</td>
					</tr>
					<tr>
						<td>
							<input type="hidden" name="form_name" value="new_document" />
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
			</div>
			';	
		}
	}
	else
	{
		$retour.='<div class="warning">Vous ne disposez pas des privil&egrave;ges n&eacute;cessaires &agravre; l\'ajout d\'un document</div>';
	}

	if (isset($_POST))
	{
		unset($_POST);
	}
	if (isset($_FILES))
	{
		unset($_FILES);
	}
	
	return($retour);
}

function delete_doc()
{
	if (isset($_SESSION['post']))
	{
		$_POST=$_SESSION['post'];
		unset($_SESSION['post']);
	}

	$priv=user_privilege_level();
	echo('<h1>Suppression d\'un document :</h1>');
	if ($priv>3) // Administrateurs - mod�rateurs
	{
		$id=-1;
		$titre="";
		$filename="";
		$warnings="";

		if (isset($_GET["document_id"]))
		{
			if (is_numeric($_GET["document_id"]) && $_GET["document_id"]>0)
			{
				$document_id=$_GET["document_id"];
				$result=@mysql_query(sprintf("SELECT document_id,name,filename FROM document WHERE document_id='%s'",mysql_real_escape_string($document_id)));
				if ($result && $row=mysql_fetch_assoc($result))
				{
					$id=$row["document_id"];
					$titre=$row["name"];
					$filename=$row["filename"];
					@mysql_free_result($result);
				}
				else
				{
					$warnings='<div class="warning">Document inexistant</div>';
				}
			}
			else
			{
				$warnings='<div class="warning">Document inexistant</div>';
			}	
		}
		else
		{
			$warnings='<div class="warning">Document &agrave; supprimer non pr&eacute;cis&eacute;</div>';
		}
		
		if (empty($warnings) && $id>0)
		{
			$affich_form=true;
			if (isset($_POST['form_name']) && $_POST['form_name']=="document_delete")
			{
				if(!isset($_POST["validation"]))
				{
					echo('<div class="warning">Vous n\'avez pas confirm&eacute; la suppression</div>');
				}
				elseif($_POST["validation"]=="on")
				{
					$affich_form=false;
					if (@unlink("rep_documents/$filename"))
					{
						echo('<div class="success">Fichier correctement supprim&eacute;</div>');
					}
					else
					{
						echo('<div class="warning">Erreur lors de la suppression du fichier</div>');
					}
					if(@mysql_query(sprintf("DELETE FROM document WHERE document_id='%s'",mysql_real_escape_string($id))))
					{
						echo('<div class="success">R&eacute;f&eacute;rence en base correctement supprim&eacute;e</div>');
					}
					else
					{
						echo('<div class="warning">Erreur lors de la suppression de la r&eacute;f&eacute;rence en base</div>');
					}
				}
			}
			// Affichage du formulaire le cas �ch�ant
			if ($affich_form)
			{
				echo('<form method="post" action="?action=remove_doc&document_id='.htmlentities($id).'">');
				echo('<br />Souhaitez-vous r&eacute;ellement supprimer le document suivant ?<br /><br />"');
				echo(htmlentities($titre).'"<br /><br />');
				echo('<input type="checkbox" name="validation" id="v_check" /><label for="v_check">Oui, supprimer !</label>');
				echo('<input type="hidden" name="form_name" value="document_delete" />&nbsp;&nbsp;&nbsp;&nbsp;<input type="submit" value="Valider" /></form>');
			}	
		}
		elseif (!empty($warnings))
		{
			echo($warnings);
		}
		
	}
	else
	{
		echo('<div class="warning">Vous ne disposez pas des privil&egrave;ges n&eacute;cessaires &agravre; la suppression d\'un document</div>');
	}
	
	if (isset($_POST))
	{
		unset($_POST);
	}
}

function edit_doc()
{
	if (isset($_SESSION['post']))
	{
		$_POST=$_SESSION['post'];
		unset($_SESSION['post']);
	}

	$priv=user_privilege_level();
	echo('<h1>Edition d\'un document :</h1>');
	if ($priv>3) // Administrateurs - mod�rateurs
	{
		$id=-1;
		$titre="";
		$description="";
		$category="";
		$warnings="";

		if (isset($_GET["document_id"]))
		{
			if (is_numeric($_GET["document_id"]) && $_GET["document_id"]>0)
			{
				$document_id=$_GET["document_id"];
				$result=@mysql_query(sprintf("SELECT document_id,name,description,category FROM document WHERE document_id='%s'",mysql_real_escape_string($document_id)));
				if ($result && $row=mysql_fetch_assoc($result))
				{
					$id=$row["document_id"];
					$titre=$row["name"];
					$description=$row["description"];
					$category=$row["category"];
					@mysql_free_result($result);
				}
				else
				{
					$warnings='<div class="warning">Document inexistant</div>';
				}
			}
			else
			{
				$warnings='<div class="warning">Document inexistant</div>';
			}	
		}
		else
		{
			$warnings='<div class="warning">Document &agrave; supprimer non pr&eacute;cis&eacute;</div>';
		}

		if (empty($warnings) && $id>0)
		{
			$affich_form=true;
			if (isset($_POST['form_name']) && $_POST['form_name']=="document_edition")
			{
				if ($priv>3)
				{
					$trait=true;
					
					if(isset($_POST["title"]) && is_string($_POST["title"]) && !empty($_POST["title"]))
					{
						$titre=$_POST["title"];
					}
					else
					{
						$trait=false;
						echo('<div class="warning">Titre incorrect</div>');
					}	
					if (isset($_POST["description"]) && is_string($_POST["description"]) && !empty($_POST["description"]))
					{
						$description=$_POST["description"];
					}
					else
					{
						$trait=false;
						echo('<div class="warning">Description incorrecte</div>');
					}
					if (isset($_POST["category"]) && is_numeric($_POST["category"]) && $_POST["category"]>0)
					{
						$category=$_POST["category"];
					}
					else
					{
						$trait=false;
						echo('<div class="warning">Cat&eacute;gorie incorrecte</div>');
					}
							
					if ($trait)
					{			
						// On v�rifie l'existence de la cat�gorie : le stockage MyIsam n'autorise pas une simple cl� �trang�re comme dans le cas des posts etc.
						$res_temp=@mysql_query(sprintf("SELECT COUNT( * ) AS NUM_ENR FROM DOCUMENT_CATEGORY WHERE CATEGORY_ID = '%s'",mysql_real_escape_string($category)));
						if($res_temp && $row=mysql_fetch_assoc($res_temp))
						{
							if ($row["NUM_ENR"]==1)
							{
								@mysql_free_result($result);
								if (@mysql_query(sprintf("UPDATE document SET name='%s',description='%s',category='%s' WHERE document_id='%s'",mysql_real_escape_string($titre),mysql_real_escape_string($description),mysql_real_escape_string($category),mysql_real_escape_string($id))))
								{
									echo('<div class="success">Document correctement modifi&eacute;</div>');
									$affich_form=false;
								}
								else
								{
									echo('<div class="warning">Erreur lors de la mise &agrave; jour du document</div>');
								}
							}
							else
							{
								echo('<div class="warning">Erreur lors de la mise &agrave; jour du document</div>');
							}
						}
						else
						{
							echo('<div class="warning">Erreur lors de la mise &agrave; jour du document</div>');
						}
					}	
				}
				else
				{
					echo('<div class="warning">Vous ne disposez pas des droits n&eacute;cessaires</div>');
				}
			}
						
			// Affichage du formulaire le cas �ch�ant
			if ($affich_form)
			{			
				echo('
				<form method="post" action="?action=edit_doc&amp;document_id='.$id.'">
					<table class="tab_form">
						<tr>
							<td>
								Titre :
							</td>
							<td>
								<input type="text" name="title" value="'.htmlentities(stripslashes($titre)).'" />
							</td>
						</tr>
						<tr>
							<td>
								Cat&eacute;gorie :
							</td>
							<td>
								<select name="category">');
								
				$tail="";
				$result=@mysql_query("SELECT category_id,category_name FROM document_category");
				if ($result)
				{
					while($row=mysql_fetch_assoc($result))
					{
						if ($category==$row["category_id"])
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
					$tail='<option value="0">D&eacute;faut</option>';
				}
				
				echo($tail.'				
								</select>
							</td>
						</tr>
						<tr>
							<td colspan="2">
								<textarea name="description" rows="15" cols="80">'.htmlentities(stripslashes($description)).'</textarea>
							</td>
						</tr>
						<tr>
							<td colspan="2">
								<input type="hidden" name="form_name" value="document_edition" />
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
		}
		elseif (!empty($warnings))
		{
			echo($warnings);
		}
		
	}
	else
	{
		echo('<div class="warning">Vous ne disposez pas des privil&egrave;ges n&eacute;cessaires &agrave; l\'&eacute;dition d\'un document</div>');
	}
	
	if (isset($_POST))
	{
		unset($_POST);
	}
}

?>
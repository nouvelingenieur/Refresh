<?php
/*
Idea
Retrieve ideas from the database for display purposes.

File

SITE/api/idea.php

Input

integer n : number of ideas per page integer p : page for pagination purposes string q : search query integer category : id of the category to search (if unspecified, then all categories)

Output

integer IDEA_ID : id
integer IDEA_CATEOGRY_ID : id of the idea's category
string IDEA_TITLE : title (non unique)
string IDEA_TEXT : text of the idea
date IDEA_DATE : date of the posting of the idea
string IDEA_AUTHOR : name of the author if available

*/


header('Content-type: application/json');

include_once("../config.php");
$info = array("data" => array("LANG" => LANG, "NOM_ECOLE" => NOM_ECOLE, "MAIL_CONTACT" => MAIL_CONTACT));

echo "Ext.util.JSONP.callback(".json_encode($info).")";

?>

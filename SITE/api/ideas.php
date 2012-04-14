<?php
/*
Idea
Retrieve ideas from the database for display purposes.

File

SITE/api/idea.php

Input

string q : search query
integer c : id of the category to search (if unspecified, then all categories)

Output

integer IDEA_ID : id
integer IDEA_CATEOGRY_ID : id of the idea's category
string IDEA_TITLE : title (non unique)
string IDEA_TEXT : text of the idea
date IDEA_DATE : date of the posting of the idea
string IDEA_AUTHOR : name of the author if available
integer IDEA_POSITIVE_VOTES : number of positive votes
integer IDEA_NEGATIVE_VOTES : number of negative votes

*/


header('Content-type: application/json');

include_once("./mysql_connect.php");

/* INPUT */
$q = set_value('q','');
$c = set_value('c',0);

$WHERE_array = array();

// q: Search query
if ($q!='') {
$WHERE_array[] = 'title LIKE "%'.$q.'%" OR text LIKE "%'.$q.'%" OR possibly_name LIKE "%'.$q.'%"';
}

// c: Category
if ($c!=0) {
$WHERE_array[] = 'category = '.$c;
}

$WHERE = '';
if (count($WHERE_array) > 0) {
	$WHERE = 'WHERE ';
	$sep = '';
	foreach ($WHERE_array as $contraint) {
		$WHERE .= $sep.'('.$contraint.')';
		$sep = ' AND ';
	}
}

/* OUTPUT */
$sql = "SELECT t.*,
v.IDEA_POSITIVE_VOTES,
v.IDEA_NEGATIVE_VOTES 
FROM
(SELECT 
	thread_id as IDEA_ID, 
	category as IDEA_CATEOGRY_ID, 
	title as IDEA_TITLE, 
	text as IDEA_TEXT, 
	date as IDEA_DATE, 
	possibly_name as IDEA_AUTHOR 
FROM thread ".$WHERE.") as t 
INNER JOIN 
(SELECT 
thread_id as IDEA_ID, 
sum(vote) as IDEA_POSITIVE_VOTES,
COUNT(*) - sum(vote) as IDEA_NEGATIVE_VOTES 
FROM vote GROUP BY thread_id ) as v 
ON t.IDEA_ID = v.IDEA_ID";

$result = $dbh->query($sql);

$array = array();

while( $row = $result->fetch(PDO::FETCH_ASSOC) ) {
$array[] = $row;
}

array_walk_recursive($array, function(&$item, $key) {
        if(is_string($item)) {
            $item = htmlentities($item);
        }
    });

echo "Ext.util.JSONP.callback(".json_encode(array("data" => $array)).")";

?>

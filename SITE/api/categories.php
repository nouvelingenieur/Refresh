<?php
/*
Categories
Get a list of all categories available in the platform.

File

SITE/api/categories.php

Input

None

Output

integer CATEOGRY_ID : id
string CATEGORY_NAME : name of the category

*/


header('Content-type: application/json');

include_once("./mysql_connect.php");

$sql = "SELECT 
	category_id as CATEOGRY_ID, 
	category_name as CATEGORY_NAME
FROM thread_category";

$result = $dbh->query($sql);
$array = array();

while( $row = $result->fetch(PDO::FETCH_ASSOC) ) {
$array[] = $row;
}


echo "Ext.util.JSONP.callback(".json_encode(array("data" => $array)).")";

?>

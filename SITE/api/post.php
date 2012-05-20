<?php
/*
Post
Post an idea to Refresh.

File

SITE/api/post.php

Input

string IDEA_TITLE : title (non unique)
string IDEA_TEXT : text of the idea
integer IDEA_CATEOGRY_ID : id of the idea's category (if 0 then default)
double IDEA_LAT : latitude (for geolocalization)
double IDEA_LONG : longitude (for geolocalization)

Output

bool IDEA_POSTED : true if idea posted correctly

*/

header('Content-type: application/json');

include_once("./mysql_connect.php");

/* INPUT */
$IDEA_TITLE = set_value('IDEA_TITLE','undef');
$IDEA_TEXT = set_value('IDEA_TEXT','');
$IDEA_CATEOGRY_ID = set_value('IDEA_CATEOGRY_ID',0);
$IDEA_LAT = set_value('IDEA_LAT',0);
$IDEA_LONG = set_value('IDEA_LONG',0);


if ($IDEA_TITLE=='undef') {
	$array = array('IDEA_POSTED' => 'False');
} else {
	$action = post($IDEA_TITLE,$IDEA_TEXT,null,$IDEA_CATEOGRY_ID,'test',$valid=1,$output='',$IDEA_LAT,$IDEA_LONG);
	if($action->result) {
		$array = array('IDEA_POSTED' => 'True');
	} else {
		$array = array('IDEA_POSTED' => 'False');
	}
}


array_walk_recursive($array, function(&$item, $key) {
        if(is_string($item)) {
            $item = htmlentities($item);
        }
    });

echo "Ext.util.JSONP.callback(".json_encode(array("data" => $array)).")";

?>

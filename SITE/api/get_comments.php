<?php
/*
get_comments
Retrieves the comment associated with an idea.

File

SITE/api/get_comments.php

Input

string EMAIL: sha1 crypted email of the user
string PASSWORD: sha1 crypted password
int IDEA_ID
Output

int comment_id: 12345
string comment_text: 'This is my comment!'
string comment_user_name: 'John'

*/


header('Content-type: application/json');

include_once("./mysql_connect.php");

/* INPUT */
$EMAIL = set_value('EMAIL','');
$PASSWORD = set_value('PASSWORD','');
$IDEA_ID = set_value('IDEA_ID','');

$result=@mysql_query(sprintf("SELECT user_id,is_valid,privileges FROM user WHERE hash_mail='%s' AND hash_pass='%s'",mysql_real_escape_string($EMAIL),mysql_real_escape_string($PASSWORD)));
if (mysql_num_rows($result)!=0)
	{

	
	$action = get_comments($IDEA_ID,1,$EMAIL,$output='');
	
	$array = $action->data;
	
	
} else {

	$array = array('SUCCESS' => 'False','MESSAGE' => _('Login Error: email and password do not match'));

}

array_walk_recursive($array, function(&$item, $key) {
	if(is_string($item)) {
		$item = htmlentities($item);
	}
});

echo "Ext.util.JSONP.callback(".json_encode(array("data" => $array)).")";

?>

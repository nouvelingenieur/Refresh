<?php
/*
Log the user into Refresh.

File

SITE/api/login.php

Input
* string EMAIL: email of the user
* string PASSWORD: password
* string SERVER_URL: url of the server

Output
* bool SUCCESS: 'True' if succes, else 'False'
* string MESSAGE: 'This User is authorized' if success, 'User doesn't exist', 'User and password do not match' 'Password is required'

*/

header('Content-type: application/json');

include_once("./mysql_connect.php");

/* INPUT */
$EMAIL = set_value('EMAIL','');
$PASSWORD = set_value('PASSWORD','');
$SERVER_URL = set_value('SERVER_URL','http://refresh.nouvelingenieur.fr');

function authentification($EMAIL,$PASSWORD) {
	if ($PASSWORD==sha1('')) {
		return array('SUCCESS' => 'False','MESSAGE' => _('Email missing'));
	}
	
	if ($EMAIL==sha1('')) {
		return array('SUCCESS' => 'False','MESSAGE' => _('Password missing'));
	}
	
	$hash_log=$EMAIL;
	$hash_pass=$PASSWORD;
	
	$result=@mysql_query(sprintf("SELECT user_id,is_valid,privileges FROM user WHERE hash_mail='%s' AND hash_pass='%s'",mysql_real_escape_string($hash_log),mysql_real_escape_string($hash_pass)));
	if (mysql_num_rows($result)==0)
	{
		return array('SUCCESS' => 'False','MESSAGE' => _('Email and password do not match'));	
	} else {
		return array('SUCCESS' => 'True','MESSAGE' => _('You are now logged in'));
	}
}

$array = authentification($EMAIL,$PASSWORD);



array_walk_recursive($array, function(&$item, $key) {
        if(is_string($item)) {
            $item = htmlentities($item);
        }
    });

echo "Ext.util.JSONP.callback(".json_encode(array("data" => $array)).")";

?>

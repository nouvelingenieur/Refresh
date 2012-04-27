<?php
include_once("../config.php");
include_once("../script_php/pages_secondlevel/actions.php");

$dbh = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASSWORD);

$link=@mysql_connect(DB_HOST,DB_USER,DB_PASSWORD);

function set_value($parameter,$default) {
	if (!isset($_GET[$parameter])) {
		return $default;
	} else {
		return $_GET[$parameter];
	}
}

?>

<?php
/*
Set Comment
Comment an idea.

File

SITE/api/set_comment.php

Input

string EMAIL: sha1 crypted email of the user
string PASSWORD: sha1 crypted password
int IDEA_ID
string COMMENT_TEXT
Output

*/


header('Content-type: application/json');

include_once("./mysql_connect.php");

/* INPUT */
$EMAIL = set_value('EMAIL','');
$PASSWORD = set_value('PASSWORD','');
$IDEA_ID = set_value('IDEA_ID','');
$COMMENT_TEXT = set_value('COMMENT_TEXT','');

$result=@mysql_query(sprintf("SELECT user_id,is_valid,privileges FROM user WHERE hash_mail='%s' AND hash_pass='%s'",mysql_real_escape_string($EMAIL),mysql_real_escape_string($PASSWORD)));
if (mysql_num_rows($result)!=0)
	{
	
	$text_back=$COMMENT_TEXT;
	$COMMENT_TEXT=mysql_real_escape_string($COMMENT_TEXT);
	$rand_prop=mt_rand(0,65535);
	$hash_prop=sha1($EMAIL.$rand_prop); // Anonymat relatif, car nombre d'adresses mails élèves dans l'école limité...

	$chaine_conf=random_password(40);
	$chaine_conf_hash=sha1($chaine_conf);
	
	list($mail,$second_part)=explode("@",$EMAIL,2);
	$name_print=mysql_real_escape_string($mail);
	
	@mysql_query("INSERT INTO `comment` (`comment_id`,`thread_id`,`rand_prop`,`hash_prop`,`text`,`date`,`is_valid`,`already_mod`,`possibly_name`,`chaine_moderation`) VALUES (NULL,'$IDEA_ID','$rand_prop','$hash_prop','$COMMENT_TEXT',CURRENT_TIMESTAMP,1,1,'$name_print','$chaine_conf_hash')");
	
	$array = array('SUCCESS' => 'True','MESSAGE' => _('Your comment was posted successfully'));
	
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

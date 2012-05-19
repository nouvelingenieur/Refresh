<?php
/*
Vote for an idea.

File

SITE/api/vote_idea.php

Input

string EMAIL: sha1 crypted email of the user
string PASSWORD: sha1 crypted password
int IDEA_ID
int VOTE_VALUE: +1 or -1
string POSSIBLY_NAME: non crypted left part of the author's email address

Output

integer IDEA_POSITIVE_VOTES : number of positive votes
integer IDEA_NEGATIVE_VOTES : number of negative votes

*/


header('Content-type: application/json');

include_once("./mysql_connect.php");

/* INPUT */
$EMAIL = set_value('EMAIL','');
$PASSWORD = set_value('PASSWORD','');
$IDEA_ID = set_value('IDEA_ID','');
$VOTE_VALUE = set_value('VOTE_VALUE','');
$POSSIBLY_NAME = set_value('POSSIBLY_NAME','');


$result=@mysql_query(sprintf("SELECT user_id,is_valid,privileges FROM user WHERE hash_mail='%s' AND hash_pass='%s'",mysql_real_escape_string($EMAIL),mysql_real_escape_string($PASSWORD)));
if (mysql_num_rows($result)!=0)
	{
	
			$result=@mysql_query(sprintf("SELECT vote_id, vote FROM vote WHERE thread_id='%s' AND CAST(SHA1(CONCAT('%s',CAST(rand_prop AS CHAR))) AS CHAR)=hash_prop",mysql_real_escape_string($IDEA_ID),mysql_real_escape_string($POSSIBLY_NAME)));
			if ($row=mysql_fetch_assoc($result)) { // already voted
				@mysql_query(sprintf("UPDATE vote SET vote=%s WHERE vote_id='%s'",$VOTE_VALUE,mysql_real_escape_string($result['vote_id'])));
			} else {
				$rand_prop=mt_rand(0,65535);
				$hash_prop=sha1($POSSIBLY_NAME.$rand_prop);
				$thread_id_sec=mysql_real_escape_string($IDEA_ID);
				@mysql_query("INSERT INTO `vote` (`vote_id`,`thread_id`,`rand_prop`,`hash_prop`,`vote`) VALUES (NULL, '$thread_id_sec','$rand_prop','$hash_prop','$VOTE_VALUE')");
			}
	
		$sql = "SELECT 
		thread_id as IDEA_ID, 
		sum(vote) as IDEA_POSITIVE_VOTES,
		COUNT(*) - sum(vote) as IDEA_NEGATIVE_VOTES 
		FROM vote WHERE thread_id = $thread_id_sec GROUP BY thread_id";

		$result = $dbh->query($sql);

		$row = $result->fetch(PDO::FETCH_ASSOC);
	
		$array = array('SUCCESS' => 'True','MESSAGE' => _('Your vote was casted successfully'),'IDEA_POSITIVE_VOTES' => $row['IDEA_POSITIVE_VOTES'],'IDEA_NEGATIVE_VOTES' => $row['IDEA_NEGATIVE_VOTES']);
	
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

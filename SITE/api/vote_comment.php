<?php
/*
Vote for a comment.

File

SITE/api/vote_comment.php

Input

string EMAIL: sha1 crypted email of the user
string PASSWORD: sha1 crypted password
integer COMMENT_ID
integer VOTE_VALUE: 1 agree or 0 disagree
string POSSIBLY_NAME: non crypted left part of the author's email address
Output

integer COMMENT_POSITIVE_VOTES : number of positive votes
integer COMMENT_NEGATIVE_VOTES : number of negative votes

*/


header('Content-type: application/json');

include_once("./mysql_connect.php");

/* INPUT */
$EMAIL = set_value('EMAIL','');
$PASSWORD = set_value('PASSWORD','');
$COMMENT_ID = set_value('COMMENT_ID','');
$VOTE_VALUE = set_value('VOTE_VALUE','');
$POSSIBLY_NAME = set_value('POSSIBLY_NAME','');


$result=@mysql_query(sprintf("SELECT user_id,is_valid,privileges FROM user WHERE hash_mail='%s' AND hash_pass='%s'",mysql_real_escape_string($EMAIL),mysql_real_escape_string($PASSWORD)));
if (mysql_num_rows($result)!=0)
	{
	
			$result=@mysql_query(sprintf("SELECT vote_comment_id, vote FROM vote_comment WHERE comment_id='%s' AND CAST(SHA1(CONCAT('%s',CAST(rand_prop AS CHAR))) AS CHAR)=hash_prop",mysql_real_escape_string($COMMENT_ID),mysql_real_escape_string($POSSIBLY_NAME)));
			if ($row=mysql_fetch_assoc($result)) { // already voted
				@mysql_query(sprintf("UPDATE vote_comment SET vote=%s WHERE vote_comment_id='%s'",$VOTE_VALUE,mysql_real_escape_string($row['vote_comment_id'])));
			} else {
				$rand_prop=mt_rand(0,65535);
				$hash_prop=sha1($POSSIBLY_NAME.$rand_prop);
				@mysql_query("INSERT INTO `vote_comment` (`vote_comment_id`,`comment_id`,`rand_prop`,`hash_prop`,`vote`) VALUES (NULL, '$COMMENT_ID','$rand_prop','$hash_prop','$VOTE_VALUE')");
			}
	
		$sql = "SELECT 
		comment_id as COMMENT_ID, 
		sum(vote) as COMMENT_POSITIVE_VOTES,
		COUNT(*) - sum(vote) as COMMENT_NEGATIVE_VOTES 
		FROM vote_comment WHERE comment_id = $COMMENT_ID GROUP BY comment_id";

		$result = $dbh->query($sql);

		$row = $result->fetch(PDO::FETCH_ASSOC);
	
		$array = array('SUCCESS' => 'True','MESSAGE' => _('Your vote was casted successfully'),'COMMENT_POSITIVE_VOTES' => $row['COMMENT_POSITIVE_VOTES'],'COMMENT_NEGATIVE_VOTES' => $row['COMMENT_NEGATIVE_VOTES']);
	
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

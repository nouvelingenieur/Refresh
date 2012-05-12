<?php

/*
	Plateforme web PPR - outil de crowdsourcing
	Copyright(C) 2011 Nicolas SEICHEPINE

	This file is part of PPR.
	
	PPR is free software: you can redistribute it and/or modify
    it under the terms of the GNU Affero General Public License as
    published by the Free Software Foundation, either version 3 of the
    License, or (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU Affero General Public License for more details.

    You should have received a copy of the GNU Affero General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
	
	Contact : nicolas.seichepine.org/?action=contact
*/

include_once("tool.php");
include_once("errors.php");
include_once("votes.php");
include_once("comments.php");

/**
 * class for action functions
 *   
 */
class action {
    var $result = False;  // Result of the action
	var $warnings = array(); // list of generated warnings
	var $successes = array(); // list of generated successes
	var $data = array();
	
	// set result
	function set_result($result) {
		$this->result = $result;
	}
	
	// add a warning
    function add_warning($warning) {
        $this->warnings[] = $warning;
    }

	// add a success
	function add_success($success) {
        $this->successes[] = $success;
    }

	// display all warnings
	function echo_warnings() {
		foreach ($this->warnings as $warning) {
			echo ("<div class='warning'>".$warning."</div>");
		}
    }

	// display all success messages
	function echo_successes() {
		foreach ($this->successes as $success) {
			echo ("<div class='success'>".$success."</div>");
		}
    }
	
	// display all results in JSON format
	function output_result($output) {
		$array = array( 'RESULT' => $this->result, 'WARNINGS' => $this->warnings, 'SUCCESSES' => $this->successes, 'DATA' => $this->data );
		
		if ($output == 'JSON') {
			echo json_encode($array);
		}
	}
}

/**
 * inserts a new idea in the database
 *
 * @param  string    $title  title of the idea
 * @param  string    $message message of the idea
 * @param  string    $anonymization tells if idea is to be anonymized
 * @param  integer   $category id of the category for the idea
 * @param  string    $login  login of the poster
 * @param  integer   $valid says if the idea needs to be moderated (default 0 = needs moderation)
 * @return array     
 */
function post($title,$message,$anonymization,$category,$login,$valid=0,$output='') {

	$action = new action;
	$action->set_result(False);

	$check_1=(isset($title) && !empty($title));
	$check_2=(isset($message) && !empty($message));
	$check_3=(!isset($anonymization) || $anonymization=="on");
	$check_4=(isset($category) && is_numeric($category) && $category>0);

	// Vérification des arguments
	if ($check_1)
	{
		$title_prec=$title;
	}
	else
	{
		$action->add_warning(_('Incorrect title'));
	}                
	if ($check_2)
	{
		$text_prec=$message;
	}
	else
	{
		$action->add_warning(_('Incorrect message'));
	}               
	if ($check_3)
	{
		if (isset($anonymization))
		{
			$anon_prec="on";
		}
	}
	else
	{
		$action->add_warning(_('Incorrect anonymization value'));
	}
	if ($check_4)
	{
		$cate_prec=$category;
	}
	else
	{
		$action->add_warning(_('Incorrect category'));
	}
	
	if ($check_1 && $check_2 && $check_3 && $check_4) // Tous les arguments sont corrects, exécution du traitement du formulaire
	{
		$title_prec_sec=mysql_real_escape_string($title_prec);
		$text_prec_sec=mysql_real_escape_string($text_prec);
		$cate_prec_sec=mysql_real_escape_string($cate_prec);
		$rand_prop=mt_rand(0,65535);
		$hash_prop=sha1($login.$rand_prop);

		if ($anon_prec=="on")
		{
			$name_print="";
		}
		else
		{
			$name_print=mysql_real_escape_string(construct_name_from_session());
		}

		if (@mysql_query("INSERT INTO `thread` (`thread_id`,`rand_prop`,`hash_prop`,`title`,`text`,`date`,`category`,`is_valid`,`possibly_name`) VALUES (NULL, '$rand_prop', '$hash_prop','$title_prec_sec','$text_prec_sec',CURRENT_TIMESTAMP,'$cate_prec_sec',$valid,'$name_print')"))
		{
			$action->add_success(_('The idea was added to Refresh and now has to be moderated'));
			$action->set_result(True);
		}
		else
		{
			$action->add_warning(_('The idea could not be added due to a database error'));
		}
	}
	
	$action->output_result($output);
	return $action;
}


/**
 * returns a list of comments for a given thread id
 *   
 */
function get_comments($thread_id,$privileges,$login,$output='') {

	$action = new action;
	$action->set_result(False);

	$escaped_threadid=mysql_real_escape_string($thread_id);
	$escaped_name=mysql_real_escape_string($login);
	$result=@mysql_query(sprintf("(SELECT C.comment_id,C.rand_prop,C.hash_prop,C.text,C.date,C.is_valid,C.already_mod,C.possibly_name,
	SUM(V.vote) AS pro_vote, COUNT(V.vote) AS total_vote, 
	MAX(CAST(SHA1(CONCAT('%s',CAST(V.rand_prop AS CHAR))) AS CHAR)=V.hash_prop) AS my_vote, 
	MAX(CAST(SHA1(CONCAT('%s',CAST(V.rand_prop AS CHAR))) AS CHAR)=V.hash_prop AND V.vote) AS my_provote
	FROM comment C, vote_comment V
	WHERE C.thread_id='%s' AND V.comment_id=C.comment_id
	GROUP BY C.comment_id,C.rand_prop,C.hash_prop,C.text,C.date,C.is_valid,C.already_mod,C.possibly_name)
	UNION
	(SELECT C.comment_id,C.rand_prop,C.hash_prop,C.text,C.date,C.is_valid,C.already_mod,C.possibly_name,
	0 AS pro_vote, 0 AS total_vote,0 AS my_vote, 0 AS my_provote
	FROM comment C
	WHERE C.thread_id='%s' AND C.comment_id<>ALL(SELECT comment_id FROM vote_comment))
	ORDER BY date ASC",$escaped_name,$escaped_name,$escaped_threadid,$escaped_threadid));
	
	
	
	if($result)
	{
		while($row=mysql_fetch_assoc($result))
		{
			$is_proprio=check_property($row["rand_prop"],$row["hash_prop"]);
			$is_valid=$row["is_valid"];

			if ($is_valid || $is_proprio || $privileges>3)
			{
				$comment = array();
				$comment['comment_id'] = $row["comment_id"]; // comment id
				$comment['is_proprio'] = check_property($row["rand_prop"],$row["hash_prop"]); // 1 if the current user has posted the comment, else 0
				$comment['is_valid'] = $row["is_valid"]; // 1 if comment has been accepted, else 0
				$comment['already_mod'] = $row["already_mod"]; // 1 if comment has already been moderated, else 0
				$comment['date'] = $row['date']; // date the comment was posted
				$comment['possibly_name'] = $row['possibly_name']; // name of the author if available
				$comment['text'] = text_display_prepare(trim($row["text"])); // text of the comment
				$comment['my_vote'] = $row['my_vote']; // 1 if current user has voted for it, else 0
				$comment['my_provote'] = $row['my_provote']; // 1 if current user has voted +1, else 0
				$comment['pro_vote'] = $row['pro_vote']; // total of +1 votes
				$comment['total_vote'] = $row['total_vote']; // total number of votes
				
				$action->data[$row["comment_id"]] = $comment;
				$action->set_result(True);
				
			}
		}
	}
	
	$action->output_result($output);
	return $action;
}

?>


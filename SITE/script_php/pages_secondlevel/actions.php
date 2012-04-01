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

/**
 * class for action functions
 *   
 */
class action {
    var $result = False;  // Result of the action
	var $warnings = array(); // list of generated warnings
	var $successes = array(); // list of generated successes
	
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
	function echo_json() {
		$array = array( 'RESULT' => $this->result, 'WARNINGS' => $this->warnings, 'SUCCESSES' => $this->successes );
		
		echo json_encode($array);
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
function post($title,$message,$anonymization,$category,$login,$valid=0) {

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
	
	$action->echo_json();
	return $action;
}

?>


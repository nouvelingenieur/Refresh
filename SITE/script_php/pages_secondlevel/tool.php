<?php

/*
	Plateforme web PPR - outil de crowdwourcing
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
	
	Contact : contact_ppr@seichepine.org
*/

include_once("connection_db.php");

function is_enpc_mail($ccar)
{
	return preg_match('#^([a-z][\-_]?)*[a-z]+\.([a-z][\-_]?)*[a-z]+@eleves\.enpc\.fr$#',strtolower($ccar));
}

function construct_name_from_session()
{
    if (is_logged())
    {
        list($mail,$second_part)=explode("@",$_SESSION['login_c'],2);
        list($prenom, $nom)=explode(".",$mail,2);
        $prenom=ucfirst(strtolower($prenom));
        $nom=ucfirst(strtolower($nom));
        return($prenom.' '.$nom);
    }
    else
    {
        return "Unknown User";
    }
}

function comes_from_etuproxy()
{
	return ip2long('195.221.194.14')==ip2long($_SERVER['REMOTE_ADDR']);
}

function comes_from_enpc()
{
	$borne_1=ip2long('194.57.247.0');
	$borne_2=ip2long('194.57.247.255');
	$borne_3=ip2long('195.221.192.0');
	$borne_4=ip2long('195.221.195.255');
	$borne_5=ip2long('195.221.197.0');
	$borne_6=ip2long('195.221.197.255');
	$ip_dem=ip2long($_SERVER['REMOTE_ADDR']);
	return (($ip_dem>=$borne_1 && $ip_dem<=$borne_2) || ($ip_dem>=$borne_3 && $ip_dem<=$borne_4) || ($ip_dem>=$borne_5 && $ip_dem<=$borne_6));
}

function is_logged()
{
	return(isset($_SESSION['login_c']) && isset($_SESSION['passw']) && isset($_SESSION['uid']) && is_numeric($_SESSION['uid']) && isset($_SESSION['privileges']) && is_numeric($_SESSION['privileges']));
}

function user_privilege_level()
{
	if (is_logged())
	{
		if ($_SESSION['privileges']==2) // LOGGE, privilèges lecture seule
		{
			return 2;
		}
		if ($_SESSION['privileges']==3) // LOGGE, privilèges normaux
		{
			return 3;
		}
		elseif ($_SESSION['privileges']==4) // MODERATEUR - CER
		{
			return 4;
		}
		elseif ($_SESSION['privileges']==5) // ADMIN
		{
			return 5;
		}
		else
		{
			return 0; // Cas suspect, autant ne pas donner de privilèges
		}
	}
	elseif (comes_from_etuproxy())
	{
		return 2; // NON LOGGE, mais accès en lecture seule
	}
	elseif (comes_from_enpc())
	{
		return 1; // NON LOGGE, mais accès en lecture seule à certaines parties
	}
	else
	{
		return 0; // Pas de privilèges
	}
}

function random_password($nb_car = 8)
{
	$choix = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789"; // Jeux de caractère qui ne risque pas de sauter dans un mail
	$nb_carac = strlen($choix);
	$pass = "";
	for($i = 0; $i < $nb_car; $i++)
	{
		$pass .= $choix[mt_rand(0,($nb_carac-1))];
	}
	return $pass;  
}

function check_property($rand_prop,$hash_prop)
{
	if (is_logged()) // Vérifie qu'un objet appartient bien à la personne loggée qui effectue la demande
	{
		return($hash_prop==sha1($_SESSION['login_c'].$rand_prop));
	}
	else
	{
		return false;
	}
}

function transfo_date($string_entr)
{
	if(strlen($string_entr)<10)
	{
		return "00/00/00";
	}
	else
	{
		list($annee,$mois,$jour)=explode("-",substr($string_entr,0,10));
		return $jour.'/'.$mois.'/'.$annee;
	}
}

?>
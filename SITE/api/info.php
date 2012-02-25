<?php
/*
Info
Returns basic info about the current configuration of the linked Refresh installation.

File

SITE/api/info.php

Input

None

Output

string LANG : language of the current Refresh installation
string NOM_ECOLE : name of the school for the current Refresg installation
string MAIL_CONTACT : contact email for the current platform

*/

header('Content-type: application/json');

include_once("../config.php");
$info = array("data" => array("LANG" => LANG, "NOM_ECOLE" => NOM_ECOLE, "MAIL_CONTACT" => MAIL_CONTACT));

echo "Ext.util.JSONP.callback(".json_encode($info).")";

?>

<?php
header('Content-type: application/json');

include_once("../config.php");
$info = array("data" => array("LANG" => LANG, "NOM_ECOLE" => NOM_ECOLE, "MAIL_CONTACT" => MAIL_CONTACT));

echo "Ext.util.JSONP.callback(".json_encode($info).")";

?>

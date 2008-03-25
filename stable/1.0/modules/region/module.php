<?php
$Module = array( "name" => "Language selector",
                 "variable_params" => true,
                 "function" => array(
                     "script" => "index.php",
                     "params" => array( ) ) );

$ViewList = array();
$ViewList["index"] = array(
    "script" => "index.php",
    'params' => array( 'siteaccess' => 'siteaccess' ) );
$ViewList["import"] = array(
    "script" => "import.php",
    'params' => array( ) );
?>

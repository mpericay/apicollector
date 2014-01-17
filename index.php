<?php
require_once("lib/class.apiretriever.php");

$apiretriever = new apiretriever();

// we make error management inside (logError function)
$apiretriever->handle();
?>

<?php 
$log = file_get_contents($_SERVER['DOCUMENT_ROOT'].'/modules/pagofacil/log.txt');
echo nl2br($log);
 ?>
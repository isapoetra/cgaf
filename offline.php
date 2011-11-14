<?php
$r = $_GET['r'];
$reasons = array('1001'=>'For security reason your address has been banned.');

$msg = isset($reasons[$r]) ? $reasons[$r] : 'Under maintenance';
echo $msg;
echo $_SERVER['REMOTE_ADDR'];
<?php if (! defined("CGAF"))	die("Restricted Access");
global $_configs;
$_configs = array(
	"System"=> array(
			"installed"=> true 
		),
	"db"=> array(
			"type"=> "json", 
			"host"=> "localhost", 
			"table_prefix"=> "", 
			"username"=> "root", 
			"password"=> "", 
			"database"=> "cgaf", 
			"debug"=> false 
		),
	"errors"=> array(
		),
);
?>
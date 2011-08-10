<?php
defined("CGAF") or die("Restricted Access");

interface IRequest {

	public function get ($varname, $default = null,$secure=true);

	public function gets ($place = null,$secure=true);
}
?>
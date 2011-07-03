<?php
if (!defined("CGAF") ) die("Restricted Access");
interface IDBAware {
	function getDBConnection();
}
?>
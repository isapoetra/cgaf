<?php
if (!defined("CGAF") ) die("Restricted Access");

interface IDBConnection {
	function Open();
	/**
	 *
	 * @param boolean
	 * @return void
	 */
	function setThrowOnError($value);
	function fetchAssoc();
}
?>
<?php
namespace System\DB;
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
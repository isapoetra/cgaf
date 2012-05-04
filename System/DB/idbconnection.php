<?php
namespace System\DB;
interface IDBConnection extends \IConnection {
	/**
	 *
	 * @param boolean
	 * @return void
	 */
	function setThrowOnError($value);
	function fetchAssoc();
	function exec($sql);
}
?>
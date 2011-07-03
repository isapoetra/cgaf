<?php

interface IACL {
	
	/**
	 *
	 * @param $id
	 * @param $group
	 * @param $access
	 * @param $userid
	 */
	function isAllow($id, $group, $access = "view", $userid = null);
	
	function filter($o, $aclgroup, $field);
	
	function isAuthentificated();
}

?>
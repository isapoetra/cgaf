<?php
namespace System\ACL;
interface IACL {
	/**
	 *
	 * @param $id
	 * @param $group
	 * @param $access
	 * @param $userid
	 * @return boolean
	 */
	function isAllow($id, $group, $access = "view", $userid = null);
	/**
	 * Enter description here ...
	 * @param unknown_type $o
	 * @param unknown_type $aclgroup
	 * @param unknown_type $field
	 * @return array
	 */
	function filter($o, $aclgroup, $field);
	/**
	 * Enter description here ...
	 * @return boolean
	 */
	function isAuthentificated();
}
?>
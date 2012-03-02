<?php
namespace System\ACL;
interface IACL {
	/**
	 *
	 * @param string $id
	 * @param string $group
	 * @param string|int $access
	 * @param null|int $userid
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
  function clearCache();
  function getUserId();
  public function isInRole($role);
}
?>
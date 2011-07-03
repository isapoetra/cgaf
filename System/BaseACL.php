<?php

abstract class BaseACL extends Object implements IACL {

	private $_appId;
	/**
	 *
	 * @var IApplication
	 */
	private $_appOwner;
	private $_cacheMode = true;
	protected  $_rolesCache = array();
	function __construct($appOwner) {
		if ($appOwner ===null) {
			$this->_appId = "__cgaf";
		}
		if ($appOwner instanceof  IApplication) {
			$appOwner->addEventListener(LoginEvent::LOGIN,array($this,"onAuth"));
			$appOwner->addEventListener(LoginEvent::LOGOUT,array($this,"onAuth"));
			$this->setAppOwner($appOwner);
		}
	}
	protected function onAuth($event) {
		//if ($event->type == LoginEvent::LOGIN) {
		$this->clearCache();
		//}
		//ppd($event);
	}
	function checkModule($moduleId,$access="view",$userId=NULL) {
		$userId = $userId ===null ? $this->getUserId() :  $userId;
		return $this->isAllow($moduleId, 'module',$access,$userId) ;
	}
	function setCacheMode($value) {
		$this->_cacheMode = $value;
	}

	protected function setAppOwner($appOwner) {
		$this->_appOwner = $appOwner;
		$this->_appId = $appOwner->getAppId ();
	}

	protected function getAppOwer() {
		return $this->_appOwner;
	}

	function filter($o, $aclgroup, $field) {
		$retval = array();
		if (is_array ( $o )) {
			foreach ( $o as $k => $row ) {
				if ($this->isAllow ( $row->$field, $aclgroup )) {
					$retval [$k] = $row;
				}
			}

			return $retval;
		} else if (is_object ( $o )) {
			if ($this->isAllow ( $o->$field, $aclgroup )) {
				return $o;
			}
			return null;
		}
		return $retval;
	}

	function getUserId() {
		$info = Session::get ( "__logonInfo", null );
		if ($info == null) {
			//guest ?
			$userid = - 1;
		} else {
			$userid = $info->user_id;
		}
		return $userid;
	}

	function getLogonInfo() {
		return Session::get ( "__logonInfo", null );
	}

	protected function getAccessAccess($access) {
		if (! is_numeric ( $access )) {
			$enum = ACLHelper::$ACCESS_ACCESSENUM;
			$access = strtolower ( $access );
			$access = isset ( $enum [$access] ) ? $enum [$access] : ACLHelper::ACL_VIEW;
		}
		return $access;
	}

	protected function getAccessValue($access) {
		if (! is_numeric ( $access )) {
			$enum = ACLHelper::$ACCESS_ENUM;
			$access = strtolower ( $access );
			return isset ( $enum [$access] ) ? $enum [$access] : $access;
		}
		return ( int ) $access;
	}

	protected function isAllowPrivs($privs, $id, $group, $access) {
		static $cache;
		$access = $this->getAccessValue ( $access );

		if ($access === (ACLHelper::ACCESS_WRITE | ACLHelper::ACCESS_UPDATE)) {
			return $this->isAllowPrivs ( $privs, $id, $group, ACLHelper::ACCESS_WRITE ) || $this->isAllowPrivs ( $privs, $id, $group, ACLHelper::ACCESS_UPDATE );
		}

		if (isset ( $cache [$group] [$id] [$access] )) {

			return $cache [$group] [$id] [$access];
		}
		/*if (isset($privs ['manage'] ['system'])) {
			$privs [$group] [$id] = $privs ['manage'] ['system'] ? $privs ['manage'] ['system'] : $privs [$group] [$id] ;
			}*/

		if (! isset ( $privs [$group] [$id] )) {
			return false;
		}
		$retval = false;
		$pr = $privs [$group] [$id];
		foreach ( $pr as $p ) {
			if (is_numeric ( $p ) && is_numeric ( $access )) {
				$retval = ($access & $p) === $access;
				break;
			} elseif (is_string ( $p ) && $access === $p) {
				$retval = true;
				break;
			}
		}
		$cache [$group] [$id] [$access] = $retval;

		return $cache [$group] [$id] [$access];
	}

	protected function getCacheManager() {
		if ($this->_appOwner) {
			//pp($this->_appOwner);
			return $this->_appOwner->getCacheManager ();
		}
		return CGAF::getCacheManager ();
	}

	protected function putCache($userid, $value) {
		if ($userid == null) {
			$userid = $this->getUserId ();
		}
		$cm = $this->getCacheManager ();
		$id = "acl-{$this->_appId}-$userid";
		return $cm->put ( $id, serialize ( $value ), "acl" );
	}

	protected function getCache($userid) {
		static $cache;

		if ($userid == null) {
			$userid = $this->getUserId ();
		}
		if (isset ( $cache [$userid] )) {
			return $cache [$userid];
		}

		$cm = $this->getCacheManager ();


		$id = "acl-{$this->_appId}-$userid";
		$cache [$userid] = unserialize ( $cm->getContent ( $id, "acl" ) );
		return $cache [$userid];
	}
	protected function clearCache() {
		$id = "acl-{$this->_appId}-" . $this->getUserId ();
		$cm = $this->getCacheManager ();
		$cm->remove ( $id, "acl" );
	}
	function removeCache($id) {
		$cm = $this->getCacheManager ();
		return $cm->remove ( $id, "acl" );
	}

	abstract function getUserInRole($rolename, $byName = true);



	function getUserRoles($userid = null) {
		if ($userid === null) {
			$userid = $this->getUserId ();
		}

		if (! isset ( $this->_rolesCache [$this->_appId] [$userid] )) {
			return $this->_rolesCache[$this->_appId][$userid];
		}
		return null;
	}

	protected  function mergePrivs(&$privs, $o) {
		foreach ( $o as $r ) {
			if (isset ( $privs [$r->object_type] [$r->object_id] )) {
				$x = $privs [$r->object_type] [$r->object_id];
				$found = false;
				foreach ( $x as $k => $v ) {
					if (is_numeric ( $v ) && is_numeric ( $r->privs )) {
						$privs [$r->object_type] [$r->object_id] [$k] = $r->privs;
						$found = true;
					}
					if (is_string ( $v ) && $v == $r->privs) {
						$found = true;
					}
				}
				if (! $found) {
					$privs [$r->object_type] [$r->object_id] [] = $r->privs;
				}
			} else {
				$privs [$r->object_type] [$r->object_id] [] = $r->privs;
			}
		}
	}

	function isAllow($id, $group, $access = "view", $userid = null) {
		$owner = $this->getAppOwer ();
		if ($owner && $owner->getConfig ( 'disableacl', false )) {
			return true;
		}
		if (CGAF::getConfig('disableacl')) {
			return true;
		}
		if ($id == null) {
			return false;
		}
		if ($userid == null) {
			$userid = $this->getUserId ();
		}
		$retval = true;
		$access = $this->getAccessValue ( $access );
		//pp($access);
		if (is_string ( $access )) {
			if ($this->isAllow ( $id, $group, $this->getAccessValue ( ACLHelper::ACL_EXT_2 ), $userid )) {
				return true;
			}
		}

		$cache = $this->getCache ( $userid );
		if ($cache) {
			$cache = is_string ( $cache ) ? unserialize ( $cache ) : $cache;
			if (isset ( $cache [$group] )) {
				//ppd($cache);
				return $this->isAllowPrivs ( $cache, $id, $group, $access );
			}
		}
		return false;


	}

	function revoke($id, $group, $access = "view", $userid = null) {
		if ($userid == null) {
			$userid = $this->getUserId ();
		}
		$userid = ( int ) $userid;
		//remove from cache
		$cache = $this->getCache ( $userid );
		if (isset ( $cache [$group] [$id] )) {
			unset ( $cache [$group] [$id] );
			$this->putCache ( $userid, $cache );
		}

	}
	protected abstract function _getRoles();
	function getRoleIdByRoleName($id) {
		static $roles;
		if (! $roles) {
			$roles = $this->_getRoles();
		}

		foreach ( $roles as $role ) {

			if (is_numeric ( $id ) && ( int ) $role->role_id === ( int ) $id) {
				return $role;
			} else if ($role->role_name === $id) {
				return $role;
			}
		}
		return null;
	}

	function assignRole($uid, $roleId) {

		$role = $this->getRoleIdByRoleName ( $roleId );
		if (! $role) {
			throw new SystemException ( 'acl.invalidrole' );
		}
		if ($this->isInrole ( $role->role_name, $uid )) {
			return true;
		}


		return true;

	}

	function grantToRole($id, $group, $roleId, $access = 'view') {

	}

	function grant($id, $group, $access = "view", $userid = null) {
		$access = $this->getAccessAccess ( $access );

		if ($this->isAllow ( $id, $group, $access, $userid )) {
			return true;
		}
		//remove from cache
		$cache = $this->getCache ( $userid );
		if (isset ( $cache [$group] [$id] )) {
			unset ( $cache [$group] [$id] );
			$this->putCache ( $userid, $cache );
		}
		$access = $this->getAccessAccess ( $access );
		$o = $this->_q->clear ()->addTable ( "user_privs" )->where ( "user_id=" . $userid )->where ( "app_id=" . $this->_q->quote ( $this->_appId ) )->where ( "object_id=" . $this->_q->quote ( $id ) )->where ( "object_type=" . $this->_q->quote ( $group ) )->loadObject ();
		if ($o) {
			$o->privs = $o->privs | $access;
			$this->_q->clear ()->addTable ( "user_privs" )->Update ( "privs", $o->privs, "=", true )->where ( "user_id=" . ( int ) $userid )->where ( "app_id=" . $this->_q->quote ( $this->_appId ) )->where ( "object_id=" . $this->_q->quote ( $id ) )->where ( "object_type=" . $this->_q->quote ( $group ) )->exec ();
		} else {
			$o = $this->_q->clear ()->addTable ( "user_privs" )->addInsert ( "user_id", ( int ) $userid )->addInsert ( "app_id", $this->_q->quote ( $this->_appId ) )->addInsert ( "object_id", $this->_q->quote ( $id ) )->addInsert ( "object_type", $this->_q->quote ( $group ) )->addInsert ( "privs", $access )->exec ();
		}
		return true;
	}

	function isInrole($roleName, $uid = null) {
		$roles = $this->getUserRoles ( $uid );

		if ($roles) {
			foreach ( $roles as $role ) {
				if (is_numeric ( $roleName ) && ( int ) $role->role_id === ( int ) $roleName) {
					return true;
				} else if ($role->role_name === $roleName) {
					return true;
				}
			}
		}
		return false;
	}

	function getUserInfo() {
		return Session::get ( "__logonInfo", null );
	}

	function isAuthentificated() {
		return Session::get ( "__auth", false ) && is_object ( Session::get ( "__logonInfo", null ) );
	}
	
	/*function &getDeniedItems ($module, $uid = null) {
		$items = array();
		if (! is_numeric($module)) {
			$m = ModuleManager::getModuleInfo($module, false);
			if ($m) {
				$module = $m->mod_id;
			}
		}
		if (! $uid) {
			$uid = $this->getUserId();
		}
		$acls = $this->getItemACLs($module, $uid);
		// If we get here we should have an array.
		if (is_array($acls)) {
			// Grab the item values
			foreach ($acls as $acl) {
				$acl_entry = $this->get_acl($acl);
				if ($acl_entry['allow'] == false && $acl_entry['enabled'] == true && isset($acl_entry['axo'][$module])) foreach ($acl_entry['axo'][$module] as $id) {
					$items[] = $id;
				}
			}
		} else {
			CGAF::trace(__FILE__, __LINE__, 2, "getDeniedItems($module, $uid) - no ACL's match", "acl");
		}
		CGAF::trace(__FILE__, __LINE__, E_NOTICE, "getDeniedItems($module, $uid) returning " . count($items) . " items", "acl");
		return $items;
	}

	// This is probably redundant.
	function &getAllowedItems ($module, $uid = null) {
		$items = array();
		if (! $uid) $uid = ACL::getUserID();
		$acls = $this->getItemACLs($module, $uid);
		if (is_array($acls)) {
			foreach ($acls as $acl) {
				$acl_entry = $this->get_acl($acl);
				if ($acl_entry['allow'] == true && $acl_entry['enabled'] == true && isset($acl_entry['axo'][$module])) {
					foreach ($acl_entry['axo'][$module] as $id) {
						$items[] = $id;
					}
				}
			}
		} else {
			CGAF::trace(__FILE__, __LINE__, E_WARNING, "getAllowedItems($module, $uid) - no ACL's match", "acl");
		}
		CGAF::trace(__FILE__, __LINE__, E_WARNING, "getAllowedItems($module, $uid) returning " . count($items) . " items", "acl");
		return $items;
	}*/

}

?>
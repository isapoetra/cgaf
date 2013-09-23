<?php
/**
 * ACL Object
 *
 */
namespace System\ACL;

use CGAF;
use System\Applications\IApplication;
use System\Exceptions\SystemException;
use System\Session\Session;

abstract class BaseACL extends \BaseObject implements IACL
{
    private $_cachePath = 'acl';
    protected $_appId;
    /**
     *
     * @var IApplication
     */
    private $_appOwner;
    /**
     * Cache Mode
     * ..
     *
     * @var boolean
     */
    private $_cacheMode = true;
    /**
     * Role Cache
     *
     * @var array
     */
    protected $_rolesCache = array();
    /**
     * Stora cached privs for user
     *
     * @var array
     */
    protected $_cacheUserPrivs = array();
    private $_groupCache = array();
    /**
     * @var \System\DB\DBQuery
     */
    private $_q;

    function __construct($appOwner)
    {
        if ($appOwner === null) {
            $this->_appId = "__cgaf";
        }
        if ($appOwner instanceof IApplication) {
            $this->setAppOwner($appOwner);
        }
    }

    public function onSessionDestroy($event)
    {
        //$this->clearCache ();
    }

    protected function onAuth($event)
    {
        //$this->clearCache ();
    }

    function checkModule($moduleId, $access = "view", $userId = NULL)
    {
        $userId = $userId === null ? $this->getUserId() : $userId;
        return $this->isAllow($moduleId, 'module', $access, $userId);
    }

    function setCacheMode($value)
    {
        $this->_cacheMode = $value;
    }

    protected function setAppOwner(IApplication $appOwner)
    {
        $this->_appOwner = $appOwner;
        $this->_appId = $appOwner->getAppId();
    }

    protected function getAppOwer()
    {
        return $this->_appOwner;
    }

    function filter($o, $aclgroup, $field)
    {
        $retval = array();
        if ($o === (array)$o) {
            foreach ($o as $k => $row) {
                if ($this->isAllow($row->$field, $aclgroup)) {
                    $retval[$k] = $row;
                }
            }
            return $retval;
        } else if (is_object($o)) {
            if ($this->isAllow($o->$field, $aclgroup)) {
                return $o;
            }
            return null;
        }
        return $retval;
    }

    function getUserId()
    {
        $info = Session::get("__logonInfo", null);
        if ($info == null) {
            // guest ?
            $userid = -1;
        } else {
            $info = $info->getUserInfo();
            $userid = $info->user_id;
        }
        return $userid;
    }

    function getLogonInfo()
    {
        return Session::get("__logonInfo", null);
    }

    /**
     * @param $access
     * @return int|string
     * @deprecated ACLHelper::stringToAccess
     */

    protected function getAccessAccess($access)
    {
        return ACLHelper::stringToAccess($access);
    }

    protected function getAccessValue($access)
    {
        if (!is_numeric($access)) {
            $enum = ACLHelper::$ACCESS_ENUM;
            $access = strtolower($access);
            return isset($enum[$access]) ? $enum[$access] : $access;
        }
        return (int)$access;
    }

    protected function isAllowPrivs($privs, $id, $group, $access)
    {
        $access = $this->getAccessValue($access);
        if ($access === (ACLHelper::ACCESS_WRITE | ACLHelper::ACCESS_UPDATE)) {
            return $this
                ->isAllowPrivs($privs, $id, $group, ACLHelper::ACCESS_WRITE)
            || $this
                ->isAllowPrivs($privs, $id, $group,
                    ACLHelper::ACCESS_UPDATE);
        }
        if (isset($this->_groupCache[$group][$id][$access])) {
            return $this->_groupCache[$group][$id][$access];
        }
        /*
         * if (isset($privs ['manage'] ['system'])) { $privs [$group] [$id] =
         * $privs ['manage'] ['system'] ? $privs ['manage'] ['system'] : $privs
         * [$group] [$id] ; }
         */
        if (!isset($privs[$group][$id])) {
            return false;
        }
        $retval = false;
        $pr = $privs[$group][$id];
        $retval = ACLHelper::isAllowAccess($pr, $access);
        $this->_groupCache[$group][$id][$access] = $retval;
        return $this->_groupCache[$group][$id][$access];
    }

    /**
     * Enter description here .
     * ..
     *
     * @return \System\Cache\Engine\ICacheEngine
     */

    protected function getCacheManager()
    {
        if ($this->_appOwner) {
            return $this->_appOwner->getInternalCache();
        }
        return CGAF::getInternalCacheManager();
    }

    protected function putCache($userid, $appId, $value)
    {
        if ($userid == null) {
            $userid = $this->getUserId();
        }
        $this->_cacheUserPrivs[$userid][$appId] = $value;
        $cm = $this->getCacheManager();
        $id = "acl-$userid-" . $this->_appId;
        return $cm->put($id, serialize($value), $this->_cachePath);
    }

    protected function removeCacheForUser($userId)
    {
        if (isset($this->_cacheUserPrivs[$userId])) {
            unset($this->_cacheUserPrivs[$userId]);
        }
        $cm = $this->getCacheManager();
        $id = "acl-$userId";
        $cm->remove($id, 'acl');
    }

    /**
     * @param int $userid
     * @param string $appId
     * @return mixed
     */
    protected function getCache($userid, $appId)
    {
        if ($userid == null) {
            $userid = $this->getUserId();
        }
        $appId = $appId ? $appId : $this->_appId;
        if (isset($this->_cacheUserPrivs[$userid][$appId])) {
            return $this->_cacheUserPrivs[$userid][$appId];
        }
        $cm = $this->getCacheManager();
        $id = "acl-$userid-" . $this->_appId;
        $this->_cacheUserPrivs[$userid][$appId] = unserialize(
            $cm->getContent($id, 'acl'));
        return $this->_cacheUserPrivs[$userid][$appId];
    }

    protected function clearUserCache($uid)
    {
        $this->_cacheUserPrivs[$uid] = array();
        $id = "acl-" . $uid;
        $cm = $this->getCacheManager();
        $cm->remove($id, "acl");
    }

    public function clearCache()
    {
        $this->clearUserCache($this->getUserId());
        $this->clearUserCache(ACLHelper::PUBLIC_USER_ID);
    }

    function removeCache($id)
    {
        $cm = $this->getCacheManager();
        return $cm->remove($id, "acl");
    }

    abstract function getUserInRole($rolename, $byName = true);

    function getUserRoles($userid = null)
    {
        if ($userid === null) {
            $userid = $this->getUserId();
        }
        if (!isset($this->_rolesCache[$this->_appId][$userid])) {
            return $this->_rolesCache[$this->_appId][$userid];
        }
        return null;
    }

    protected function mergePrivs(&$privs, $o)
    {
        foreach ($o as $r) {
            if (isset($privs[$r->object_type][$r->object_id])) {
                $x = $privs[$r->object_type][$r->object_id];
                $found = false;
                foreach ($x as $k => $v) {
                    if (is_numeric($v) && is_numeric($r->privs)) {
                        $privs[$r->object_type][$r->object_id][$k] = $r->privs;
                        $found = true;
                    }
                    if (is_string($v) && $v == $r->privs) {
                        $found = true;
                    }
                }
                if (!$found) {
                    $privs[$r->object_type][$r->object_id][] = $r->privs;
                }
            } else {
                $privs[$r->object_type][$r->object_id][] = $r->privs;
            }
        }
    }

    function getUserPriv($userid, $id, $group, $appId, $force = false)
    {
        $cache = $this->getCache($userid, $appId);
        if (isset($cache[$group]) && isset($cache[$group][$id])) {
            return $cache[$group][$id];
        }
    }

    protected function getUserPrivs($userid, $id, $appId, $force = false)
    {
        $cache = $this->getCache($userid, $appId);
        return $cache;
    }

    function isAllow($id, $group, $access = "view", $userid = null)
    {
        if (!\CGAF::isInstalled()) {
            return false;
        }
        if ($id === CGAF::APP_ID && $group === ACLHelper::APP_GROUP
            && $access === 'view'
        ) {
            return true;
        }
        $owner = $this->getAppOwer();
        if ($owner && $owner->getConfig('disableacl', false)) {
            return true;
        }
        if (CGAF::getConfig('disableacl')) {
            return true;
        }
        if ($id == null) {
            return false;
        }
        if ($userid == null) {
            $userid = $this->getUserId();
        }
        if (\CGAF::getACL()->isInrole(ACLHelper::DEV_GROUP)) {
            return true;
        }
        $retval = true;
        $access = ACLHelper::stringToAccess($access);
        if (is_string($access)) {
            if ($this
                ->isAllow($id, $group,
                    $this->getAccessValue(ACLHelper::ACL_EXT_2),
                    $userid)
            ) {
                return true;
            }
        }
        $cache = $this->getCache($userid, $this->_appId);
        //if ($this->_appId !== CGAF::APP_ID) ppd($cache);
        if ($cache) {
            $cache = is_string($cache) ? unserialize($cache) : $cache;
            if (isset($cache[$group])) {
                return $this->isAllowPrivs($cache, $id, $group, $access);
            }
        }
        return null;
    }

    function revoke($id, $group, $access = "view", $userid = null)
    {
        if ($userid == null) {
            $userid = $this->getUserId();
        }
        $userid = (int)$userid;
        // remove from cache
        $cache = $this->getCache($userid, $this->_appId);
        if (isset($cache[$group][$id])) {
            unset($cache[$group][$id]);
            $this->putCache($userid, $this->_appId, $cache);
        }
    }

    protected abstract function _getRoles();

    function getRoleIdByRoleName($id)
    {
        $roles = $this->_getRoles();
        foreach ($roles as $role) {
            if (is_numeric($id) && (int)$role->role_id === (int)$id) {
                return $role;
            } else if ($role->role_name === $id) {
                return $role;
            }
        }
        return null;
    }

    function assignRole($uid, $roleId)
    {
        $role = $this->getRoleIdByRoleName($roleId);
        if (!$role) {
            throw new SystemException('acl.invalidrole');
        }
        if ($this->isInrole($role->role_name, $uid)) {
            return true;
        }
        return true;
    }

    /**
     * @param $id
     * @param $group
     * @param $roleId
     * @param $appId
     * @param string $access
     * @return bool
     */
    function grantToRole($id, $group, $roleId, $appId, $access = 'view')
    {
        $uroles = $this->getUserInRole($roleId, false);
        foreach ($uroles as $r) {
            $this->clearUserCache($r->user_id);
        }
        return true;
    }

    /**
     * @param $id
     * @param $group
     * @param string $access
     * @param null $userid
     * @return bool
     */
    function grant($id, $group, $access = "view", $userid = null)
    {
        $access = ACLHelper::stringToAccess($access);
        if ($this->isAllow($id, $group, $access, $userid)) {
            return true;
        }
        // remove from cache
        $cache = $this->getCache($userid, $this->_appId);
        if (isset($cache[$group][$id])) {
            unset($cache[$group][$id]);
            $this->putCache($userid, $this->_appId, $cache);
        }
        $access = ACLHelper::stringToAccess($access);
        $o = $this->_q->clear()->addTable("user_privs")
            ->where("user_id=" . $userid)
            ->where("app_id=" . $this->_q->quote($this->_appId))
            ->where("object_id=" . $this->_q->quote($id))
            ->where("object_type=" . $this->_q->quote($group))
            ->loadObject();
        if ($o) {
            $o->privs = $o->privs | $access;
            $this->_q->clear()->addTable("user_privs")
                ->Update("privs", $o->privs, "=", true)
                ->where("user_id=" . (int)$userid)
                ->where("app_id=" . $this->_q->quote($this->_appId))
                ->where("object_id=" . $this->_q->quote($id))
                ->where("object_type=" . $this->_q->quote($group))->exec();
        } else {
            $this->_q->clear()->addTable("user_privs")
                ->addInsert("user_id", (int)$userid)
                ->addInsert("app_id", $this->_q->quote($this->_appId))
                ->addInsert("object_id", $this->_q->quote($id))
                ->addInsert("object_type", $this->_q->quote($group))
                ->addInsert("privs", $access)->exec();
        }
        return true;
    }

    function isInRole($roleName, $uid = null)
    {
        $owner = $this->getAppOwer();
        if ($owner && $owner->getConfig('disableacl', false)) {
            return true;
        }
        $roles = $this->getUserRoles($uid);
        // ppd($roles);
        if ($roles) {
            foreach ($roles as $role) {
                if (is_numeric($roleName)
                    && (int)$role->role_id === (int)$roleName
                ) {
                    return true;
                } else if ($role->role_name === $roleName) {
                    return true;
                }
            }
        }
        return false;
    }

    function getUserInfo()
    {
        return Session::get("__logonInfo", null);
    }

    function isAuthentificated()
    {
        return Session::get("__auth", false)
        && is_object(Session::get("__logonInfo", null));
    }

    /**
     * @param $objectId
     * @param $objectGroup
     * @param $appId
     * @param $roleId
     * @param string $access
     * @return bool
     */
    function revokeFromRole($objectId, $objectGroup, $appId, $roleId,
                            $access = 'view')
    {
        $users = $this->getUserInRole($roleId, false, $appId);
        foreach ($users as $u) {
            $this->clearUserCache($u->user_id);
        }
        return true;
    }
}

?>
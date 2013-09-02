<?php
namespace System\ACL\Provider;
use System\ACL\ACLHelper;
use System\ACL\BaseACL;
use System\DB\DBQuery;


use /** @noinspection PhpUndefinedNamespaceInspection */
    System\Models\User;
use /** @noinspection PhpUndefinedNamespaceInspection */
    System\Models\UserRoles;
use AppManager;
use System\MVC\Model;

class Db extends BaseACL
{
    protected $_q;
    private $_roles;
    private $_userObjects;

    function __construct($appOwner)
    {
        parent::__construct($appOwner);
        $this->_q = new DBQuery (\CGAF::getDBConnection());
    }

    function isPartner()
    {
        return $this->isInrole(ACLHelper::PARTNERS_GROUP);
    }

    function isDevelover()
    {
        return $this->isInrole(ACLHelper::DEV_GROUP);
    }

    function isAdmin()
    {
        return $this->isInrole(ACLHelper::ADMINS_GROUP);
    }

    function isMember()
    {
        return $this->isInrole(ACLHelper::ADMINS_GROUP) || $this->isInrole(ACLHelper::MEMBERS_GROUP) || $this->isInrole(ACLHelper::PARTNERS_GROUP);
    }

    private function _getRole($roleId)
    {
        $q = $this->_q;
        $q->clear();
        $q->addTable("roles", "r");
        $q->Where('role_id=' . $roleId);
        $q->where("r.app_id=" . $q->quote($this->_appId));
        return $q->loadObject();
    }

    function revoke($id, $group, $access = "view", $userid = null)
    {
        if ($userid == null) {
            $userid = $this->getUserId();
        }
        $userid = ( int )$userid;
        parent::revoke($id, $group, $access, $userid);
        $access = $this->getAccessAccess($access);
        $o = $this->_q->clear()->addTable("user_privs")->where("user_id=" . $userid)->where("app_id=" . $this->_q->quote($this->_appId))->where("object_id=" . $this->_q->quote($id))->where("object_type=" . $this->_q->quote($group))->loadObject();
        if ($o) {
            $o->privs &= ~$access;
            if ($o->privs == 0) {
                $this->_q->clear()->setMode("delete")->addTable("user_privs")->where("user_id=" . ( int )$userid)->where("app_id=" . $this->_q->quote($this->_appId))->where("object_id=" . $this->_q->quote($id))->where("object_type=" . $this->_q->quote($group))->exec();
            } else {
                $this->_q->clear()->addTable("user_privs")->Update("privs", $o->privs, "=", true)->where("user_id=" . ( int )$userid)->where("app_id=" . $this->_q->quote($this->_appId))->where("object_id=" . $this->_q->quote($id))->where("object_type=" . $this->_q->quote($group))->exec();
            }
        }
    }

    function _getRoles()
    {
        if (!$this->_roles) {
            $this->_roles = $this->_q->clear()->addTable("roles", "ur")->loadObjects();
        }
        return $this->_roles;
    }

    function assignRole($uid, $roleId)
    {
        if (parent::assignRole($uid, $roleId)) {
            $roleId = $this->getRoleIdByRoleName($roleId);
            if (!$this->_q->clear()
                ->addTable('user_roles')
                ->insert('app_id', $this->_q->quote($this->getAppOwer()->getAppId()))
                ->insert('user_id', $uid)
                ->insert('role_id', $roleId)
                ->exec()
            ) {
                $this->setLastError($this->_q->getLastError());
            }
        }
        return true;
    }

    function getUserRoles($userid = null)
    {
        if ($userid === null) {
            $userid = $this->getUserId();
        }
        if (!isset ($this->_rolesCache [$this->_appId] [$userid])) {
            $retval = array();
            /** @noinspection PhpUndefinedClassInspection */
            /**
             * @var Model $ur
             */
            $ur = new UserRoles ();
            $ur->reset();
            $ur->Where("ur.user_id=" . ( int )$userid);
            if (AppManager::isAppStarted()) {
                $app = AppManager::getInstance();
                if ($app->getAppId() !== $this->_appId) {
                    $ur->where("(ur.app_id=" . $ur->quote($this->_appId) . ' or ur.app_id=' . $ur->quote($app->getAppId()) . ')');
                } elseif ($this->_appId !== \CGAF::APP_ID) {
                    $ur->where("ur.app_id=" . $ur->quote($this->_appId));
                }
            } elseif ($this->_appId !== \CGAF::APP_ID) {
                $ur->where("ur.app_id=" . $ur->quote($this->_appId));
            }
            $ur->where("ur.active=1");

            $roles = $ur->loadObjects();
            if ($roles) {
                foreach ($roles as $role) {
                    $retval [] = $role;
                    if (( int )$role->role_parent !== -1) {
                        $rtemp = $this->_getRole($role->role_parent);
                        if ($rtemp) {
                            $retval [] = $rtemp;
                        }
                    }
                }
                $this->_rolesCache [$this->_appId] [$userid] = $retval;
            } else {
                $this->_rolesCache [$this->_appId] [$userid] = array();
            }
        }

        return $this->_rolesCache [$this->_appId] [$userid];
    }

    function getUserInRole($rolename, $byName = true, $appId = null)
    {
        $q = $this->_q;
        $q->clear();
        $q->addTable("user_roles", "ur");
        $q->select("ur.*,r.role_name,u.user_name");
        $q->join("roles", "r", "ur.role_id=r.role_id and r.app_id=" . $this->_q->quote($appId ? $appId : $this->_appId));
        $q->join('users', 'u', 'ur.user_id=u.user_id and ur.app_id=' . $this->_q->quote($appId ? $appId : $this->_appId));
        $q->where("ur.app_id=" . $this->_q->quote($appId ? $appId : $this->_appId));
        $q->where("ur.active=1");
        if ($byName) {
            $q->where('role_name=' . $this->_q->quote($rolename));
        } else {
            $q->where('ur.role_id=' . $this->_q->quote($rolename));
        }
        $roles = $q->loadObjects();
        return $roles;
    }

    /**
     * @param $user_id
     * @param $role
     * @return bool|null|\System\DB\DBResultList
     */
    function addUserToRole($user_id, $role)
    {
        if (!$this->isInrole($role, $user_id)) {
            $q = $this->_q;
            $role_id = $q->clear()->addTable("roles")->where("role_name=" . $q->quote($role))->loadObject()->role_id;
            if ($role_id != null) {
                $appId = $this->getAppOwer()->getAppId();
                $q->clear()->addTable("user_roles")->insert("role_id", $role_id)->insert("user_id", ( int )$user_id)->insert("app_id", $q->quote($appId))->insert("active", 1);
                return $q->exec();
            }
        }
        return null;
    }

    /**
     * @param $userid
     * @param $id
     * @param $group
     * @param $appId
     * @param bool $force
     * @return mixed
     */
    function getUserPriv($userid, $id, $group, $appId, $force = false)
    {
        $cache = parent::getUserPriv($userid, $id, $group, $appId, $force);
        if (!$force && $cache) {
            return $cache;
        }
        $this->getUserPrivs($userid, $id, $group, $appId);
        return parent::getUserPriv($userid, $id, $group, $appId, $force);
    }

    protected function getUserPrivs($userid, $id, $appId, $force = false)
    {
        if (!$force) {
            if ($retval = parent::getUserPrivs($userid, $id, $appId, false)) {
                return $retval;
            }
        }
        $roles = $this->getUserRoles($userid);
        $userRole = array();
        foreach ($roles as $role) {
            if (( int )$role->role_id !== ACLHelper::GUEST_ROLE_ID) {
                $userRole [] = $role->role_id;
            }
        }
        $q = $this->_q;
        $privs = array();
        // get from public roles
        $q->clear();
        $q->addTable("role_privs");
        if ($this->_appId && $this->_appId !== '__cgaf') {
            $q->Where("(role_id=0");
            $q->Where("app_id=" . $q->Quote($appId) . ')', 'or');
        } else {
            $q->Where("role_id=0");
        }
        if ($this->_appId !== "__cgaf") {
            $q->Where("role_id=0 and app_id=" . $q->Quote("__cgaf"));
        }

        $rprivs = $q->loadObjects();
        if ($rprivs) {
            foreach ($rprivs as $r) {
                $privs [$r->object_type] [$r->object_id] [] = $r->privs;
            }
        }
        if (count($userRole)) {
            $q->clear();
            $q->addTable('role_privs');
            $q->Where('role_id in (' . implode($userRole, ',') . ')');
            $q->where("app_id=" . $q->Quote($appId));
            \Logger::write($q->getSQL(), 'acl-check');
            $rprivs = $q->loadObjects();
            $this->mergePrivs($privs, $rprivs);
        }
        $q->clear();
        $q->addTable("user_privs");
        $q->Where("user_id=" . ( int )$userid);
        $q->where("app_id=" . $q->Quote($appId));
        $uprivs = $q->loadObjects();
        $this->mergePrivs($privs, $uprivs);
        $this->putCache($userid, $appId, $privs);
        return $privs;
    }

    function isAllow($id, $group, $access = "view", $userid = null)
    {
        if ($id === \CGAF::APP_ID && $group === ACLHelper::APP_GROUP && $access === 'view') {
            return true;
        }
        if ($userid == null) {
            $userid = $this->getUserId();
        }
        //$this->removeCacheForUser($userid);
        $cache = $this->getCache($userid, $this->_appId);

        if ($cache) {
            $r = parent::isAllow($id, $group, $access, $userid);
            if ($r !== null) {
                return $r;
            }
        }
        if (isset($this->_userObjects[$userid])) {
            $o = $this->_userObjects[$userid];
        } else {
            /** @noinspection PhpUndefinedClassInspection */
            /**
             * @var Model $u;
             */
            $u = new User ();
            $this->_userObjects[$userid] = $u->load(( int )$userid);
        }
        if ($this->_userObjects[$userid] == null) {
            return false;
        }
        $this->getUserPrivs($userid, $id, $this->_appId);
        return parent::isAllow($id, $group, $access, $userid);
    }

    function revokeFromRole($objectId, $objectGroup, $appId, $roleId, $access = 'view')
    {
        $q = $this->_q;
        $q->addTable('role_privs');
        $q->Where('app_id=' . $q->quote($appId))
            ->Where('role_id=' . $q->quote($roleId))
            ->Where('object_id=' . $q->quote($objectId))
            ->Where('object_type=' . $q->quote($objectGroup));
        $o = $q->loadObject();
        if ($o) {
            $d = ACLHelper::revokePrivs($o->privs, $access);
            $q->clear();
            $q->addTable('role_privs');
            $q->Update('privs', $d);
            $q->Where('app_id=' . $q->quote($appId))
                ->Where('role_id=' . $q->quote($roleId))
                ->Where('object_id=' . $q->quote($objectId))
                ->Where('object_type=' . $q->quote($objectGroup));
            $q->exec();
        }

        return parent::revokeFromRole($objectId, $objectGroup, $appId, $roleId, $access);
    }

    function grantToRole($objectId, $objectGroup, $roleId, $appId, $access = 'view')
    {

        $q = $this->_q->clear();
        $q->addTable('role_privs');
        $q->Where('app_id=' . $q->quote($appId))
            ->Where('role_id=' . $q->quote($roleId))
            ->Where('object_id=' . $q->quote($objectId))
            ->Where('object_type=' . $q->quote($objectGroup));
        $o = $q->loadObject();
        if ($o) {
            $access = ACLHelper::grantAccess($o->privs, $access);
        } else {
            $access = ACLHelper::grantAccess(0, $access);

        }
        if (is_string($access) && $access === $o->privs) {
            return parent::grantToRole($objectId, $objectGroup, $roleId, $appId, $access);
        }
        $q = $q->clear()->addTable('role_privs');
        if ($o) {
            $q->Update('privs', $access);
            $q->Where('app_id=' . $q->quote($appId))
                ->Where('role_id=' . $q->quote($roleId))
                ->Where('object_id=' . $q->quote($objectId))
                ->Where('object_type=' . $q->quote($objectGroup));

        } else {
            $q->insert('app_id', $appId)
                ->insert('role_id', $roleId)
                ->insert('object_id', $objectId)
                ->insert('app_id', $appId)
                ->insert('object_type', ACLHelper::APP_GROUP)
                ->insert('privs', $access);
        }
        $q->exec();
        return parent::grantToRole($objectId, $objectGroup, $roleId, $appId, $access);
    }
}

<?php

namespace System\ACL\Provider;
use \System\ACL\ACLHelper;
use \CGAF, \System\Session\Session;
use \System\ACL\BaseACL;
use \System\DB\DBQuery;
use \System\Models\User;
use \System\Models\UserRoles;
use \AppManager;

class Db extends BaseACL {

    protected $_q;

    function __construct($appOwner) {
        parent::__construct($appOwner);
        $this->_q = new DBQuery(CGAF::getDBConnection ());
    }

    function isPartner() {
        return $this->isInrole(ACLHelper::PARTNERS_GROUP);
    }

    function isAdmin() {
        return $this->isInrole(ACLHelper::ADMINS_GROUP);
    }

    function isMember() {

        return $this->isInrole(ACLHelper::ADMINS_GROUP) || $this->isInrole(ACLHelper::MEMBERS_GROUP) || $this->isInrole(ACLHelper::PARTNERS_GROUP);
    }

    private function _getRole($roleId) {
        $q = $this->_q;
        $q->clear();
        $q->addTable("roles", "r");
        $q->Where('role_id=' . $roleId);
        $q->where("r.app_id=" . $q->quote($this->_appId));
        return $q->loadObject();
    }

    function revoke($id, $group, $access = "view", $userid = null) {
        if ($userid == null) {
            $userid = $this->getUserId();
        }
        $userid = (int) $userid;
        parent::revoke($id, $group, $access, $userid);
        $access = $this->getAccessAccess($access);
        $o = $this->_q->clear()->addTable("user_privs")->where("user_id=" . $userid)->where("app_id=" . $this->_q->quote($this->_appId))->where("object_id=" . $this->_q->quote($id))->where("object_type=" . $this->_q->quote($group))->loadObject();
        if ($o) {
            $o->privs &= ~ $access;
            if ($o->privs == 0) {
                $this->_q->clear()->setMode("delete")->addTable("user_privs")->where("user_id=" . (int) $userid)->where("app_id=" . $this->_q->quote($this->_appId))->where("object_id=" . $this->_q->quote($id))->where("object_type=" . $this->_q->quote($group))->exec();
            } else {
                $this->_q->clear()->addTable("user_privs")->Update("privs", $o->privs, "=", true)->where("user_id=" . (int) $userid)->where("app_id=" . $this->_q->quote($this->_appId))->where("object_id=" . $this->_q->quote($id))->where("object_type=" . $this->_q->quote($group))->exec();
            }
        }
    }

    function _getRoles() {
        static $roles;
        if (!$roles) {
            $roles = $this->_q->clear()->addTable("roles", "ur")->loadObjects();
        }
        return $roles;
    }

    function assignRole($uid, $roleId) {
        if (parent::assignRole($uid, $roleId)) {
            if (!$this->_q->clear()->addTable('user_roles')->insert('app_id', $this->_q->quote($this->getAppOwer()->getAppId()))->insert('user_id', $uid)->insert('role_id', $role->role_id)->exec()) {
                $this->_lastError = $this->_q->getLastError();
            }
        }
        return true;
    }

    function getUserRoles($userid = null) {
        static $_roles;
        if ($userid === null) {
            $userid = $this->getUserId();
        }

        if (!isset($this->_rolesCache [$this->_appId] [$userid])) {
            $retval = array();
            $ur = new UserRoles ();
            $ur->reset();
            $ur->Where("ur.user_id=" . (int) $userid);

            if (AppManager::isAppStarted ()) {
                $app = AppManager::getInstance ();
                if ($app->getAppId() !== $this->_appId) {
                    $ur->where("(ur.app_id=" . $ur->quote($this->_appId) . ' or ur.app_id=' . $ur->quote($app->getAppId()) . ')');
                } else {
                    $ur->where("ur.app_id=" . $ur->quote($this->_appId));
                }
            } else {
                $ur->where("ur.app_id=" . $ur->quote($this->_appId));
            }

            $ur->where("ur.active=1");
            //ppd($ur->getSQL());
            $roles = $ur->loadObjects();
            if ($roles) {
                foreach ($roles as $role) {
                    $retval [] = $role;
                    if ((int) $role->role_parent !== - 1) {
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
            //ppd($_roles);
        }
        return $this->_rolesCache [$this->_appId] [$userid];
    }

    function getUserInRole($rolename, $byName = true) {
        $q = $this->_q;
        $q->clear();
        $q->addTable("user_roles", "ur");
        $q->select("ur.*,r.role_name,u.user_name");
        $q->addJoin("roles", "r", "ur.role_id=r.role_id and r.app_id=" . $this->_q->quote($this->_appId));
        $q->addJoin('users', 'u', 'ur.user_id=u.user_id and ur.app_id=' . $this->_q->quote($this->_appId));
        $q->where("ur.app_id=" . $this->_q->quote($this->_appId));
        $q->where("ur.active=1");
        if ($byName) {
            $q->where('role_name=' . $this->_q->quote($rolename));
        } else {
            $q->where('ur.role_id=' . $this->_q->quote($rolename));
        }
        $roles = $q->loadObjects();
        return $roles;
    }

    function addUserToRole($user_id, $role) {
        if (!$this->isInrole($role, $user_id)) {
            $q = $this->_q;
            $role_id = $q->clear()->addTable("roles")
                            ->where("role_name=" . $q->quote($role))
                            ->loadObject()->role_id;
            if ($role_id != null) {
                $appId = $this->getAppOwer()->getAppId();
                $q->clear()
                        ->addTable("user_roles")
                        ->insert("role_id", $role_id)
                        ->insert("user_id", (int) $user_id)
                        ->insert("app_id", $q->quote($appId))
                        ->insert("active", 1);
                return $q->exec();
            }
        }
    }

    function isAllow($id, $group, $access = "view", $userid = null) {
        if ($userid == null) {
            $userid = $this->getUserId();
        }

        $cache = $this->getCache($userid);

        if ($cache) {
            return parent::isAllow($id, $group, $access, $userid);
        }

        $u = new User();
        $o = $u->load((int) $userid);
        if ($o == null || ((int) $o->user_status === 0)) {
            $retval = false;
        }

        $roles = $this->getUserRoles($userid);


        $userRole = array();
        foreach ($roles as $role) {
            if ((int) $role->role_id !== ACLHelper::GUEST_ROLE_ID) {
                $userRole [] = $role->role_id;
            }
        }
        $q = $this->_q;
        $privs = array();
        //get from public roles
        $q->clear();
        $q->addTable("role_privs");
        $q->Where("(role_id=0");
        $q->Where("app_id=" . $q->Quote($this->_appId) . ')', 'or');
        if ($this->_appId !== "__cgaf") {
            $q->Where("(role_id=0 and app_id=" . $q->Quote("__cgaf") . ")");
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
            $q->where("app_id=" . $q->Quote($this->_appId));
            $rprivs = $q->loadObjects();
            $this->mergePrivs($privs, $rprivs);
        }
        $q->clear();
        $q->addTable("user_privs");
        $q->Where("user_id=" . (int) $userid);
        $q->where("app_id=" . $q->Quote($this->_appId));
        $uprivs = $q->loadObjects();
        $this->mergePrivs($privs, $uprivs);
        $this->putCache($userid, $privs);
        return parent::isAllow($id, $group, $access, $userid);
    }

}
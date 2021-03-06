<?php
namespace System\ACL;

use AppManager;
use CGAF;

class ACLHelper
{
    const APP_GROUP = "app";
    const ADMINS_GROUP = "administrators";
    const MEMBERS_GROUP = "members";
    const OWNER_GROUP = 'owners';
    const PUBLICS_GROUP = "publics";
    const PARTNERS_GROUP = "partners";
    const DEV_GROUP = 'developers';
    const PUBLIC_USER_ID = -1;
    //TODO get from configuration // check
    const GUEST_ROLE_ID = 0;
    const ACL_VIEW = 1;
    const ACL_READ = 2;
    const ACL_WRITE = 4;
    const ACL_UPDATE = 8;
    const ACL_MANAGE = 16;
    const ACL_EXT_1 = 32; // for future use
    const ACL_EXT_2 = 64; // for future use
    const ACCESS_VIEW = 1;
    const ACCESS_READ = 2;
    const ACCESS_WRITE = 4;
    const ACCESS_UPDATE = 8;
    const ACCESS_MANAGE = 16;
    const ACCESS_EXT_1 = 32; // for future use
    const ACCESS_EXT_2 = 64; // for future use
    const ACCESS_MAX = 255;
    //for check
    public static $ACCESS_ENUM = array(
        "view" => ACLHelper::ACL_VIEW,
        "access" => ACLHelper::ACL_VIEW,
        "read" => ACLHelper::ACL_READ,
        'add' => ACLHelper::ACL_WRITE,
        "write" => ACLHelper::ACL_WRITE,
        "edit" => ACLHelper::ACL_UPDATE,
        "save" => ACLHelper::ACL_UPDATE,
        "update" => ACLHelper::ACL_UPDATE,
        "manage" => ACLHelper::ACL_MANAGE,
        "ext1" => ACLHelper::ACL_EXT_1,
        "ext2" => ACLHelper::ACL_EXT_2
    );
    //for grant / revoke
    public static $ACCESS_ACCESSENUM = array(
        "access" => ACLHelper::ACCESS_VIEW,
        "read" => ACLHelper::ACCESS_READ,
        "edit" => ACLHelper::ACCESS_WRITE,
        'add' => ACLHelper::ACCESS_WRITE,
        "write" => ACLHelper::ACCESS_WRITE,
        "update" => ACLHelper::ACCESS_UPDATE,
        "manage" => ACLHelper::ACCESS_MANAGE,
        "ext1" => ACLHelper::ACCESS_EXT_1,
        "ext2" => ACLHelper::ACCESS_EXT_2,
        "MAX" => ACLHelper::ACCESS_MAX
    );

    public static function getUserId()
    {
        $data = AppManager::getInstance()->getAuthInfo();

        return $data ? $data->getUserInfo()->user_id : self::PUBLIC_USER_ID;
    }

    /**
     *
     * Enter description here ...
     * @return \System\ACL\IACL
     */
    public static function getInstance()
    {
        return AppManager::getInstance()->getACL();
    }

    /**
     * check user if in role
     * @param mixed $rolename
     */
    public static function isInrole($rolename)
    {
        return self::getInstance()->isInrole($rolename);
    }

    public static function isAuthentificated()
    {
        return self::getInstance()->isAuthentificated();
    }

    public static function isAllowUID($uid = null, $access = "view", $rnull = false)
    {
        //$data = AppManager::getInstance()->getAuthInfo();
        $uid = $uid == null ? self::getUserId() : $uid;
        if ((int)$uid !== (int)ACLHelper::getUserId()) {
            return AppManager::getInstance()->getACL()->isAdmin() ? $uid : ACLHelper::getUserId();
        }
        return $rnull ? null : (int)$uid;
    }

    public static function getAuthInfo()
    {
        return AppManager::getInstance()->getAuthInfo();
    }

    public static function checkAppModule($modid, $op = 'view', $user = null, $appOwner = null)
    {
        if ($appOwner == null) {
            $modid = \ModuleManager::getModuleInfo($modid, false);
            if (!$modid) {
                return false;
            }
            $appOwner = $modid->app_id;
            $modid = $modid->mod_id;
            if ($user === null) {
                $user = self::getUserID();
            }
        }
        $acl = AppManager::getInstance($appOwner);
        if (!$acl)
            return false;
        return $acl->isAllow($modid, 'modules', $op, $user);
    }

    /**
     *
     * Enter description here ...
     * @param string $type
     * @param mixed $app
     * @return IACL
     */
    public static function getACLInstance($type, $app)
    {
        $cname = '\\System\\ACL\\Provider\\' . $type;
        return new $cname($app);
    }

    public static function secureFile($file, $allowPath = true)
    {
        $bad_chars = array(
            "'",
            "*",
            "?",
            "..",
            ".",
            "\0",
            "\n"
        );
        if (!$allowPath) {
            $file = \Utils::ToDirectory($file, false);
            $bad_chars[] = DS;
        }
        foreach ($bad_chars as $v) {
            while (strpos($file, $v)) {
                $file = str_ireplace($v, "", $file);
            }
        }
        return \Utils::fixFileName($file);
    }

    public static function revokePrivs($realAccess, $access = 'view')
    {
        $access = self::stringToAccess($access);
        return $realAccess ^ $access;
    }

    public static function isAllowAccess($access, $access2 = 'view')
    {
        $access2 = self::stringToAccess($access2);
        if ($access === null || $access === 0) {
            return false;
        }
        $retval = false;
        if ($access === (array)$access) {
            foreach ($access as $p) {
                if (is_numeric($access2) && is_numeric($p)) {
                    if ((int)$p === 0) return false;
                    $retval = (int)($p & $access2) === (int)$access2;
                    break;
                } elseif (is_string($access2) && $p === $access2) {
                    $retval = true;
                    break;
                }

            }
            return $retval;
        } elseif (is_numeric($access2) && is_numeric($access)) {
            $retval = ($access & $access2) === $access;
        } elseif (is_string($access2) && $access === $access2) {
            $retval = true;
        }

        return $retval;

    }

    public static function stringToAccess($access)
    {
        if (!is_numeric($access)) {
            $enum = ACLHelper::$ACCESS_ACCESSENUM;
            $access = strtolower($access);
            $access = isset ($enum [$access]) ? $enum [$access] : ACLHelper::ACL_VIEW;
        }
        return $access;
    }

    public static function grantAccess($ori, $access)
    {
        $access = self::stringToAccess($access);
        if (is_string($access)) {
            return $access;
        }
        return $ori | $access;
    }
}

?>
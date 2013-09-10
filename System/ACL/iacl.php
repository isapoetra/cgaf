<?php
namespace System\ACL;

interface IACL
{
    /**
     *
     * @param string $id
     * @param string $group
     * @param string|int $access
     * @param null|int $userid
     * @return bool
     */
    function isAllow($id, $group, $access = "view", $userid = null);

    /**
     * Enter description here ...
     * @param string $o
     * @param string $aclgroup
     * @param mixed $field
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

    function isInRole($role, $uid = null);

    function assignRole($uid, $roleId);

    function revokeFromRole($objectId, $objectGroup, $appId, $roleId, $access = 'view');

    /**
     * @return bool
     */
    function isAdmin();

    /**
     * @param $id
     * @param $group
     * @param string $access
     * @param null $userid
     * @return mixed
     */
    function grant($id, $group, $access = "view", $userid = null);
}

?>
<?php
/**
 * ----- mediawatch ----
 * User: Iwan Sapoetra
 * Date: 9/11/13
 * Time: 12:22 AM
 *
 */

namespace System\Web\Security;


class Security
{

    private static $_securityInstance = array();

    /**
     * @param $filter
     * @return IWebSecurity
     */
    public static function getInstance($filter)
    {
        if (!isset(self::$_securityInstance[$filter])) {
            $c = '\\System\\Web\\Security\\' . $filter;
            self::$_securityInstance[$filter] = new $c();
        }
        return self::$_securityInstance[$filter];
    }
}
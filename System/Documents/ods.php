<?php
namespace System\Documents;
abstract class ODS extends \BaseObject
{
    static function open($f)
    {
        if (is_file($f)) {
            $ext = strtolower(\Utils::getFileExt($f, false));
            switch ($ext) {
                case 'ods':
                    $c = "System\\Documents\\ODS\\calc";
                    break;
                default:
            }
            $instance = new $c($f);
        }
        return $instance;
    }
}

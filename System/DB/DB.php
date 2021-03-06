<?php
namespace System\DB;


use AppManager;
use System\Applications\IApplication;
use System\MVC\Model;

abstract class DB
{
    const DB_FETCH_OBJECT = 0;

    /**
     *
     * @param
     *         Array
     * @return IDBConnection
     */

    public static function Connect($connArgs)
    {
        if (!$connArgs) {
            return \CGAF::getDBConnection();
        }
        $connArgs["type"] = isset($connArgs["type"]) ? $connArgs["type"]
            : "mysql";
        $class = "\\System\\DB\\Adapters\\" . $connArgs['type'];
        /**
         * @var DBConnection $retval
         */
        $retval = new $class($connArgs);
        $retval->Open();
        return $retval;
        // throw new DBException("Database Class [$class] Not Found");
    }

    public static function loadObjectLists($sql, $conn = null)
    {
        $conn = $conn ? $conn : \AppManager::getInstance()->getDBConnection();
        $q = new DBQuery($conn);
        $q->addSQL($sql);
        return $q->loadObjects();
    }

    public static function loadHashList($sql, $conn, $hashkeyIndex = 0,
                                        $hashValIndex = 1)
    {
        $objs = self::loadObjectLists($sql, $conn);
        $retval = array();
        if (!$objs) {
            return $retval;
        }
        $first = $objs[0];
        $f = array_keys(get_object_vars($first));
        $fk = $f[$hashkeyIndex];
        $fv = $f[$hashValIndex];
        if (!$fk || !$fv) {
            throw new DBException("Unable to find hashkey");
        }
        foreach ($objs as $v) {
            $k = $v->$fk;
            $vv = $v->$fv;
            $retval[$k] = $vv;
        }
        return $retval;
    }

    public static function lookup($name, $appOwner = null)
    {
        $appId = -1;
        if ($appOwner == null) {
            $appOwner = AppManager::getInstance();
            $appId = $appOwner->getAppId();
        } else {
            if (!is_object($appOwner)) {
                $appId = $appOwner;
                $appOwner = AppManager::getInstance();
            }
        }
        /**
         * @var Model $lookup
         */
        $lookup = $appOwner->getModel('lookup');
        $rows = $lookup->setIncludeAppId(false)->clear()
            ->select("`key`,`value`,`descr`")
            ->where('app_id=' . $lookup->quote($appId))
            ->where('lookup_id=' . $lookup->quote($name))->loadObjects();
        return $rows;
    }

    public static function loadScalar($sql, $conn)
    {
        $q = new DBQuery($conn);
        $q->addSQL($sql);
        return $q->loadScalar();
    }

    public static function lookupValue($name, $value)
    {
        $rows = self::lookup($name);
        if (!count($rows)) {
            return 'Unknown ' . (CGAF_DEBUG ? $name : '');
        }
        foreach ($rows as $v) {
            if ($v->key == $value) {
                return $v->value;
            }
        }
        return null;
    }

    public static function getPKFromTable($tableName, $con = null,
                                          $returnstring = false)
    {
        $con = $con ? $con : AppManager::getInstance()->getDBConnection();
        $tinfo = $con->getObjectInfo($tableName);
        $pk = array();
        foreach ($tinfo as $v) {
            if ($v->primary) {
                $pk[] = $v->field_name;
            }
        }
        return $returnstring ? implode(',', $pk) : $pk;
    }
    public static function  exec($sql,$conn=null) {
        $q= new DBQuery($conn);
        return $q->exec($sql);
    }
    public static function dumpDB(IApplication $app, $configs = null)
    {
        $configs = \Utils::arrayMerge($configs,
            array(
                'includecgaf' => true
            ));
        $db = $app->getDBConnection();
        $lists = $db->getTableList();
        $dir = $app->getInternalStorage('install/db/common/');
        \Utils::makeDir($dir);

        while ($tbl = $lists->next()) {
            /** @noinspection PhpUndefinedFieldInspection */
            $fname = $dir . $tbl->table_name . '.sql';
            /** @noinspection PhpUndefinedFieldInspection */
            /** @noinspection PhpUndefinedMethodInspection */
            DBUtil::dumpRows($db, $tbl->table_name, $tbl->getRows(), $fname);
        }
        if ($configs['includecgaf']) {
            DBUtil::dumpCGAFAppDB($app->getAppId(), $dir . 'cgaf.sql');
        }
        return $dir;
    }

    public static function getSupportedDrivers()
    {
        $files = \Utils::getDirFiles(CGAF_PATH . 'System/DB/Adapters/', null, false, '/\.php$/i');
        $items = array();
        foreach ($files as $v) {
            $x = \Utils::getFileName($v, false);
            $items[] = \Utils::getFileName($v, false);
        }
        return $items;
    }
}

?>

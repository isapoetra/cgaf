<?php
namespace System\DB;

use Logger;
use Strings;
use System\Cache\CacheFactory;
use System\Cache\Engine\ICacheEngine;

abstract class DBConnection implements IDBConnection
{
    /**
     * @var ICacheEngine
     */
    private  $_cacheEngine=null;
    protected $_connArgs;
    private $_connected = false;
    /**
     * @var mixed
     */
    protected $_resource;
    protected $_lastError;
    protected $_lastErrorCode;
    protected $_thows = CGAF_DEBUG;
    protected $_db = null;
    protected $_result;
    protected $_database;
    protected $_lastSQL;

    function __construct($connArgs)
    {
        $this->_connArgs = $connArgs;
    }

    function __get($name)
    {
        return $this->getArg($name, null);
    }

    function getDatabase()
    {
        return $this->_database ? $this->_database : $this->getArg('database');
    }

    /**
     * @param \Exception $ex
     * @throws \Exception
     */

    protected function throwError(\Exception $ex)
    {
        $this->_lastError = $ex->getMessage() . ' SQL:' . $this->getLastSQL();
        if (CGAF_DEBUG) {
            $ex = new \Exception($this->_lastError);
        }
        Logger::write('SQL Error : ' . $this->_lastError, 'sql', false);
        if ($this->_thows) {
            throw $ex;
        }
    }

    function setThrowOnError($value)
    {
        $this->_thows = $value;
    }

    function getArgs()
    {
        return $this->_connArgs;
    }

    function getArg($name, $default = null)
    {
        return isset($this->_connArgs[$name]) ? $this->_connArgs[$name]
            : $default;
    }

    protected function setArg($name, $value)
    {
        $this->_connArgs[$name] = $value;
    }

    function quoteTable($table, $includedbname = false)
    {
        if (is_array($table)) {
            $retval = array();
            foreach ($table as $k => $v) {
                $retval[] = $this->quoteTable($v);
            }
            return implode(',', $retval);
        }
        return $table;
    }

    function isConnected()
    {
        return $this->_connected;
    }

    function Log($msg, $level = 'db')
    {
        if ($this->getArg("debug")) {
            Logger::write("DB:: $msg", $level);
        }
    }

    abstract function Query($sql);

    abstract function getObjectInfo($objectId, $objectType = "table",
                                    $throw = true);

    abstract function parseFieldCreate($name, $type, $width, $args = null);

    abstract function getLimitExpr($start, $end);

    abstract function fetchObject();

    abstract function isObjectExist($objectName, $objectType);

    abstract function getSQLCreateTable($o);

    function SelectDB($db)
    {
        $this->_db = $db;
    }

    function getTableInfo($tableName, $infoType, $nameOnly = true)
    {
        $info = $this->getObjectInfo($tableName);
        $retval = array();
        foreach ($info as $i) {
            switch (strtolower($infoType)) {
                case 'fields':
                    if ($nameOnly) {
                        $retval[] = array(
                            'name' => $i->field_name
                        );
                    } else {
                        $retval[] = $i;
                    }
                    break;
                default:
                    ;
                    break;
            }
        }
        return $retval;
    }

    function setConnected($value)
    {
        $this->_connected = $value;
    }

    function quote($s, $prep = true)
    {
        return $s;
    }

    function Open()
    {
        if ($this->_connected) {
            return true;
        }
        return $this->_connected;
    }

    function Close()
    {
        $this->_connected = false;
    }

    function execBatch($sql)
    {
        $t = $this->_thows;
        $this->_thows = true;
        $r = new DBResultList();
        foreach ($sql as $s) {
            if (!empty($s)) {
                $r->Assign($this->exec($s));
            }
        }
        $this->_thows = $t;
        return $r;
    }

    public function getLastSQL()
    {
        return $this->_lastSQL;
    }

    protected function setLastSQL($sql)
    {
        $this->_lastSQL = $sql;
    }

    protected function first(&$r)
    {
    }

    protected function unQuoteField($field)
    {
        return $field;
    }

    public abstract function getLastInsertId();

    public abstract function getAffectedRow();

    public abstract function createDBObjectFromClass($classInstance,
                                                     $objecttype, $objectName);

    /**
     * @return null|DBResultList
     */
    protected function toResultList()
    {
        $r = null;
        if ($this->_result) {
            $r = new DBResultList();
            $r->setLastInsertId($this->getLastInsertId());
            $r->setAffectedRow($this->getAffectedRow());
            $this->first($r);
            while ($row = $this->fetchObject()) {
                $s = new \stdClass();
                foreach ($row as $k => $v) {
                    $f = $this->unQuoteField($k);
                    $s->$f = $v;
                }
                $r->Assign($s);
            }
        }
        $this->putCached($this->getLastSQL(),$r);
        return $r;
    }

    protected function toTableName($tbl)
    {
        $sql = str_ireplace("[table_prefix]", $this->getArg("table_prefix"),
            $tbl);
        if (Strings::BeginWith($tbl, $this->getArg("table_prefix"))) {
            return $tbl;
        }
        if (!Strings::BeginWith($tbl, '#__')) {
            $tbl = '#__' . $tbl;
        }
        $tbl = str_ireplace("#__", $this->getArg("table_prefix"), $tbl);
        return $tbl;
    }

    protected function prepareQuery($sql)
    {
        if (Strings::BeginWith($sql, 'drop', false)
            || Strings::BeginWith($sql, 'create', false)
        ) {
            $this->_objects = array();
        }
        $sql = str_ireplace("[table_prefix]", $this->getArg("table_prefix"),
            $sql);
        $sql = str_ireplace("#__", $this->getArg("table_prefix"), $sql);
        $this->_lastSQL = $sql;
        return $sql;
    }

    public function DateToDB($date = null)
    {
        $dt = new \CDate($date);
        return $dt->format(\CDate::FMT_DATETIME_MYSQL);
    }

    public function drop($id, $type = 'table')
    {
        if ($this->isObjectExist($id, $type)) {
            $this->Exec('drop ' . $type . ' ' . $this->toTableName($id));
        }
    }

    public abstract function dropDB();

    /**
     * @param string $table
     *
     * @return string|null
     */

    public function getInstallFile($table)
    {
        $paths = array();
        $check = \CGAF::isInstalled();
        if ($check) {
            $paths[] = \CGAF::getInternalStorage(
                'install/db/' . $this->getArg('type') . '/', $check, false);
            $paths[] = \CGAF::getInternalStorage('install/db/common/', $check,
                false);
        }
        $paths[] = \CGAF::getInternalStorage(
            'install/db/' . $this->getArg('type') . '/', false, false);
        $paths[] = \CGAF::getInternalStorage('install/db/common/', false, false);
        foreach ($paths as $path) {
            $file = \Utils::ToDirectory($path . $table . '.sql');
            if (is_file($file)) {
                return $file;
            }
        }
        return null;
    }

    public function getFieldConfig($fieldType = null)
    {
        return array();
    }


    //Cache Manager
    protected function getCacheEngine() {
        if ($this->getArg('cache.enabled')) {
            if (!$this->_cacheEngine) {
            //default cache timeout 10 minutes
            $timeout = $this->getArg('cachetimeout',10);
            $this->_cacheEngine = CacheFactory::getInstance(true,$this->getArg('cache.engine','MemCached'));
            $this->_cacheEngine->setCacheTimeOut($timeout);
            }
        }
        return $this->_cacheEngine;
    }
    protected function getCached($sql) {
        if ($ce =  $this->getCacheEngine()) {
            return $ce->get(md5($sql),'sql');
        }
        return null;
    }
    protected function putCached($sql,$result) {
        if ($ce =  $this->getCacheEngine()) {
            return $ce->put(md5($sql),$result,'sql');
        }
        return null;
    }
}

?>

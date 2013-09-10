<?php
namespace System\DB\Adapters;

use CGAF;
use Logger;
use System;
use System\DB\DBException;
use Utils;

class DBSQLite3Adapter extends DBSQLiteAdapter
{

    function __construct($connArgs)
    {
        if (!class_exists('SQLite3')) {
            System::loadExtenstion('sqlite3');
        }
        parent::__construct($connArgs);
    }

    function Open()
    {
        if ($this->isConnected()) {
            return true;
        }
        $host = Utils::ToDirectory($this->getArg("host"));
        if (!$host) {
            $host = CGAF::getInternalStorage('db', false);
        }
        $host = realpath(Utils::ToDirectory($host));
        if (!$host) {
            Logger::WriteDebug($host);
            throw new DBException('Sqlite storage not found');
        }
        $file = $host . DS . $this->getArg('database') . '.sqlite';
        $this->_lastError = null;
        if (!is_file($file) && $this->getArg('autocreate', CGAF_DEBUG)) {
            $this->_resource = new \SQlite3($file, SQLITE3_OPEN_READWRITE,
                $this->getArg('password'));
        } else {
            $this->_resource = new \SQlite3($file, SQLITE3_OPEN_READWRITE,
                $this->getArg('password'));
        }
        if ($this->_resource === false) {
            $lerror = CGAF::getLastError();
            throw new DBException($lerror);
        }
        $this->SelectDB($file);
        $this->setConnected($this->_resource != false);
        return $this->isConnected();
    }

    public function getLastInsertId()
    {
        return $this->_resource->lastInsertRowID();
    }

    function Query($sql)
    {
        $sql = $this->prepareQuery($sql);
        $this->Log($sql);
        $this->_result = $this->_resource->query($sql);
        $this->_checkError();
        return $this->toResultList();
    }

    function fetchObject()
    {
        if ($this->_result instanceof \SQLite3Result) {
            $retval = new \stdClass();
            $arr = $this->_result->fetchArray();
            if ($arr) {
                return Utils::bindToObject($retval, $arr, true);
            }
            return null;
        }
        return null;
    }

    function fetchAssoc()
    {
        if ($this->_result instanceof \SQLite3Result) {
            $arr = $this->_result->fetchArray();
            ppd($arr);
        }
        return null;
    }

    function getAffectedRow()
    {
        return $this->_resource->changes();
    }

    private function _checkError()
    {
        $this->_lastError = $this->_resource->lastErrorMsg();
        $this->_lastErrorCode = $this->_resource->lastErrorCode();
        if ($this->_result == false && $this->_lastErrorCode !== 0) {
            $this
                ->throwError(
                    new DBException(
                        $this->_lastErrorCode . ':'
                        . $this->_lastError));
        }
    }

    function exec($sql)
    {
        $sql = $this->prepareQuery($sql);
        $this->Log($sql);
        $this->_result = $this->_resource->exec($sql);
        $this->_checkError();
        return $this->toResultList();
    }

    public function fieldTypeToPHP($type)
    {
        $retval = "String";
        switch (strtolower($type)) {
            case null:
            case 'unknown_type':
            case 'text':
            case 'varchar':
                $retval = 'String';
                break;
            default:
                ppd($type);
        }
        return $retval;
    }
}

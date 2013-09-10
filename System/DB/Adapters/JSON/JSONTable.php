<?php
namespace System\DB\Adapters\JSON;

use System\DB\Adapters\JSON;
use System\DB\DBException;
use System\DB\DBQuery;

class JSONTable extends DBQuery
{
    private $_db;
    private $_table;
    private $_defs;
    private $_tableDefs;
    protected $_path;
    private $_pk;
    private $_updates;
    private $_lastInsertId = -1;

    function __construct(JSON $db, $t)
    {
        parent::__construct($db);
        $this->_table = $t;
        $this->_db = $db;
        $this->_path = $this->_db->getPathFor($this->_table, 'table');
        $this->loadDefs();
        if (!isset($this->_tableDefs->DataConfig)) {
            $this->_tableDefs->DataConfig = new \stdClass();
        }
    }

    private function loadDefs()
    {
        $f = $this->_path . 'defs.json';
        if (is_readable($f)) {
            $this->_tableDefs = json_decode(file_get_contents($f));
        } else {
            throw new DBException('table not found ' . $t . '@' . $f);
        }
        if (!isset($this->_tableDefs)) {
            $this->_tableDefs = new \stdClass();
        }
    }

    private function saveDefs()
    {
        $f = $this->_path . 'defs.json';
        if (is_file($f)) {
            file_put_contents($this->_path . 'defs.json', json_encode($this->_tableDefs));
        }
    }

    function __destruct()
    {
        $this->saveDefs();
    }

    protected function getDataConfig($name, $def = null)
    {
        if (!isset($this->_tableDefs->DataConfig->$name)) {
            if ($def !== null) {
                $this->_tableDefs->DataConfig->$name = $def;
            }
            return $def;
        }
        return $this->_tableDefs->DataConfig->$name;
    }

    function setDataConfig($name, $value)
    {
        if (!isset($this->_tableDefs->DataConfig)) {
            $this->_tableDefs->DataConfig = new \stdClass();
        }
        $this->_tableDefs->DataConfig->$name = $value;
        $this->saveDefs();
    }

    function getNextAutoIncrement()
    {
        return $this->getDataConfig('increment', 0) + 1;
    }

    function getConnection()
    {
        return $this->_db;
    }

    function getTableName($includeDBName = false, $quote = true)
    {
        return $this->_table;
    }

    function hasField($fieldname)
    {
        $fields = $this->_getFieldDefs();
        return isset($fields[$fieldname]) ? true : false;
    }

    function getField($fieldName)
    {
        $fields = $this->_getFieldDefs();
        return isset($fields[$fieldName]) ? $fields[$fieldName] : null;
    }

    function getFields()
    {
        return $this->_getFieldDefs();
    }

    function getPrimaryKey()
    {
        if (!$this->_pk) {
            $this->_pk = array();
            foreach ($this->Fields as $v) {
                if ($v->primary) {
                    $this->_pk[] = $v->field_name;
                }
            }
        }
        return $this->_pk;
    }

    protected function _getFieldDefs()
    {
        if (!$this->_defs) {
            $this->_defs = array();
            foreach ($this->_tableDefs->fields as $v) {
                $o = new JSONFieldInfo($this);
                foreach ($v as $k => $j) {
                    $o->$k = $j;
                }
                $this->_defs[$v->field_name] = $o;
            }
        }
        return $this->_defs;
    }

    function getFieldInfo($field)
    {
        $fields = $this->_getFieldDefs();
        return isset($fields[$field]) ? $fields[$field] : null;
    }

    protected function load()
    {
        // TODO Optimize and Split based on config
        $f = $this->_path . 'rows.json';
        $rows = array();
        if (is_file($f)) {
            $rows = json_decode(file_get_contents($f));
        }
        return $rows;
    }

    private function rrmdir($dir)
    {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object != "." && $object != "..") {
                    if (filetype($dir . "/" . $object) == "dir") {
                        $this->rrmdir($dir . "/" . $object);
                    } else {
                        unlink($dir . "/" . $object);
                    }
                }
            }
            reset($objects);
            rmdir($dir);
        }
    }

    function drop($object = null, $what = "table")
    {
        if ($this->_db->isAllow('drop', 'table', $this->_table)) {
            try {
                // \Utils::removeFile($this->_path,true,true);
                $this->rrmdir($this->_path);
                clearstatcache(true, $this->_path);
            } catch (\Exception $e) {
                return false;
            }
            return true;
        }
    }

    private function _isDuplicate($v)
    {
        $cpk = $this->PrimaryKey;
        $rows = $this->load();
        //$retval = array ();
        //$f = $this->Fields;
        foreach ($rows as $r) {
            $eq = false;
            foreach ($cpk as $p) {
                $eq = $r->$p === $v[$p];
            }
            if ($eq) {
                return true;
            }
        }
        return false;
    }

    private function getFileFor($row)
    {
        return $this->_path . 'rows.json';
    }

    protected function deleteRows()
    {
        /**
         * @todo remove when stable
         */
        if (!CGAF_DEBUG) {
            throw new DBException('Delete command without key not allowed');
        } else {
            $fname = $this->getFileFor(null);
            $this->setDataConfig('numrow', 0);
            @unlink($fname);
            clearstatcache(true, $fname);
        }
    }

    protected function _putRow($row)
    {
        $f = $this->getFileFor($row);
        $rows = array();
        if (is_file($f)) {
            $rows = json_decode(file_get_contents($f));
        }
        $found = false;
        foreach ($rows as $idx => $r) {
            $pks = $this->getPrimaryKey();
            $found = false;
            if ($pks) {
                foreach ($pks as $pk) {
                    $found = $r->$pk === (is_array($row) ? $row[$pk] : $row->$pk);
                }
            }
            if ($found) {
                $found = $idx;
                break;
            }
        }
        if ($found) {
            $rows[$found] = $row;
        } else {
            $rows[] = $row;
        }
        file_put_contents($f, json_encode($rows));
        return $this;
    }

    function insert($def, $value = null, $func = false)
    {
        $this->clear();
        $row = new \stdClass();
        $cf = $this->getFields();
        foreach ($def as $k => $v) {
            $def[$k] = trim($v, '\'\' ');
        }
        $this->_lastInsertId = -1;
        $valid = array();
        foreach ($cf as $f) {
            $v = isset($def[$f->field_name]) ? $def[$f->field_name] : null;
            /** @noinspection PhpUndefinedMethodInspection */
            if ($f->isValid($v)) {
                $valid[$f->field_name] = $v;
            } else {
                $this->_db->_throwError(3001, $f->field_name, ' for table ' . $this->_table);
            }
            if ($f->auto_inc) {
                $this->_lastInsertId = $v;
                $this->setDataConfig('increment', $v);
            }
        }
        if ($this->_isDuplicate($valid)) {
            $this->_lastInsertId = -1;
            throw new DBException('Duplicate Row');
        }
        $this->_putRow($valid);
        $this->setDataConfig('numrow', $this->getDataConfig('numrow', 0) + 1);
        return true;
    }

    function getLastInsertId()
    {
        return $this->_lastInsertId;
    }
}

?>
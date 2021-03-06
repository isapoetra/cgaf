<?php
namespace System\DB;

use Logger;
use StdClass;
use System\Applications\IApplication;
use Utils;

class DBQuery extends \BaseObject implements IQuery
{
    const MODE_SELECT = 'select';
    const MODE_INSERT = 'insert';
    const MODE_UPDATE = 'update';
    const MODE_DELETE = 'delete';
    const MODE_DROP = 'drop';
    const MODE_CREATE_TABLE = 'create.table';
    const MODE_DIRECT = 'direct';
    /**
     * @var DBConnection
     */
    private $_conn;
    private $_table = array();
    protected $_where = array();
    protected $_type = "select";
    protected $_update = array();
    private $_lastSQL;
    private $_sql = array();
    private $_inserts = array();
    private $_drops = array();
    private $_orderby = array();
    private $_prepared = false;
    protected $_join = array();
    private $_groupBy = array();
    private $_having = array();
    private $_distinct = false;
    protected $_fields = array();
    private $_unions = array();
    public $_throwOnError = true;
    private $_validModes = array(
        DBQuery::MODE_SELECT,
        DBQuery::MODE_UPDATE,
        DBQuery::MODE_DROP,
        DBQuery::MODE_INSERT,
        DBQuery::MODE_DELETE,
        DBQuery::MODE_DIRECT,
        DBQuery::MODE_CREATE_TABLE
    );

    function setThrowOnError($value)
    {
        $this->_throwOnError = $value;
    }

    /**
     * @param $connection IDBConnection|IApplication
     */
    function __construct($connection = null)
    {
        if ($connection instanceof IApplication) {
            $this->_conn = $connection->getDBConnection();
        } elseif ($connection instanceof IDBConnection) {
            if ($connection == null) {
                $connection = \AppManager::getInstance()->getDBConnection();
            }
            $this->_conn = $connection;
        } else {
            throw new DBException ('unknown connection type');
        }
        $this->Initialize();
    }

    protected function Initialize()
    {
    }

    /**
     *
     * @return \System\DB\DBConnection
     */
    function getConnection()
    {
        return $this->_conn;
    }

    function toDate($o = null)
    {
        return $this->getConnection()->DateToDB($o);
    }

    function lastSQL()
    {
        return $this->_lastSQL;
    }

    function getDriverString()
    {
        return $this->_conn->getArg("type");
    }

    /**
     *
     * @param $str string
     * @param bool $pref
     * @return string
     */
    function quote($str, $pref = true)
    {
        if (is_array($str)) {
            $retval = array();
            foreach ($str as $k => $s) {
                $retval[$k] = $this->quote($s);
            }
            return $retval;
        }
        return $this->getConnection()->quote($str, $pref);
    }

    function quoteTable($str, $includedbname = false)
    {
        return $this->getConnection()->quoteTable($str, $includedbname);
    }

    /**
     * clear
     *
     * @param string $what
     * @return DBQuery
     */
    function clear($what = 'all')
    {
        switch (strtolower($what)) {
            case 'union' :
                $this->_unions = array();
                break;
            case 'table' :
                $this->_table = array();
                $this->_update = array();
                $this->_inserts = array();
                $this->_join = array();
                break;
            case 'join' :
                $this->_join = array();
                break;
            case 'where' :
                $this->_where = array();
                $this->_having = array();
                break;
            case 'type' :
                $this->_type = 'select';
                break;
            case 'select' :
            case 'field' :
                $this->_fields = array();
                break;
            case 'groupby' :
                $this->_groupBy = array();
                break;
            case 'orderby' :
                $this->_orderby = array();
                break;
            default :
                $this->_table = array();
                $this->_update = array();
                $this->_inserts = array();
                $this->_where = array();
                $this->_type = 'select';
                $this->_fields = array();
                $this->_join = array();
                $this->_sql = array();
                $this->_groupBy = array();
                $this->_inserts = array();
                $this->_drops = array();
                $this->_prepared = false;
                $this->_orderby = array();
                $this->_distinct = false;
                $this->_unions = array();
                $this->_having = array();
                break;
        }
        return $this;
    }

    /**
     * Enter description here...
     *
     * @param        $field string
     * @param        $value mixed
     * @param string $ope
     * @param bool $func
     * @return DBQuery
     */
    function Update($field, $value, $ope = "=", $func = false)
    {

        $this->_type = "update";
        $this->_update [$field] = array(
            $ope,
            $value,
            $func
        );
        return $this;
    }

    /**
     * @param string $order
     * @return DBQuery
     */
    function addOrder($order)
    {
        return $this->orderBy($order);
    }

    /**
     *
     * @param
     *          $order
     * @return DBQuery
     */
    function orderBy($order)
    {
        if (is_array($order)) {
            foreach ($order as $o) {
                $this->orderBy($o);
            }
            return $this;
        }
        $order = explode(',', $order);
        foreach ($order as $o) {
            $o = explode(' ', $o);
            $ob = 'asc';
            if (isset ($o [1])) {
                $ob = $o [1];
            }
            $this->_orderby [] = array(
                'field' => $o [0],
                'order' => $ob
            );
        }
        return $this;
    }

    /**
     * @param        $where
     * @param string $next
     * @return DBQuery
     */
    function Where($where, $next = 'AND')
    {
        if (is_array($where)) {
            foreach ($where as $v) {
                if (is_array($v)) {
                    $this->_where [] = $v;
                } else {
                    $this->where($v, $next);
                }
            }
            return $this;
        }
        foreach ($this->_where as $v) {
            if ($v [0] === $where) {
                return $this;
            }
        }
        $this->_where [] = array(
            $where,
            $next
        );
        return $this;
    }

    protected function getValidMode()
    {
        return $this->_validModes;
    }

    protected function isValidMode($mode)
    {
        $valid = $this->getValidMode();
        return in_array($mode, $valid);
    }

    /**
     *
     * @param $value String
     * @return DBQuery
     */
    public function setMode($value)
    {
        if (!$this->isValidMode($value)) {
            throw new DBException ('Invalid Mode ' . $value);
        }
        $this->_type = $value;
        return $this;
    }

    public function setDistinct($value)
    {
        $this->_distinct = $value;
        return $this;
    }

    function SplitSQL($file, $delimiter = ';')
    {
    }

    function loadSQLFile($file)
    {
        //original sourcecode http://stackoverflow.com/questions/1883079/best-practice-import-mysql-file-in-php-split-queries
        $delimiter = ';';
        set_time_limit(0);
        if (is_file($file) === true) {
            $file = fopen($file, 'r');
            if (is_resource($file) === true) {
                $query = array();
                while (feof($file) === false) {
                    $query [] = fgets($file);
                    if (preg_match('~' . preg_quote($delimiter, '~') . '\s*$~iS', end($query)) === 1) {
                        $query = trim(implode('', $query));
                        $this->addSQL($query);
                    }
                    if (is_string($query) === true) {
                        $query = array();
                    }
                }
                fclose($file);
            }
        }
        $this->_type = "direct";
        if (count($this->_sql) > 0) {
            return $this;
        }
        return false;
    }

    /**
     * @param $sql  string
     * @return DBQuery
     */
    function addSQL($sql)
    {
        $this->_type = "direct";
        $this->_sql [] = $sql;
        return $this;
    }

    function addInsert($field, $value = null, $func = false)
    {
        return $this->insert($field, $value, $func);
    }

    /**
     * @param      $field
     * @param null $value
     * @param bool $func
     * @return DBQuery
     */
    function insert($field, $value = null, $func = false)
    {
        $this->_type = "insert";
        if (is_array($field)) {
            foreach ($field as $k => $v) {
                if (is_array($v)) {
                    $this->clear();
                    $this->_type = "insert";
                    foreach ($v as $f => $val) {
                        $this->insert($f, $val, $func);
                    }
                    $this->exec();
                    return $this;
                } else {
                    $this->insert($k, $v);
                }
            }
        } else {
            if ($value === null) {
                $value = "null";
            }
            $this->_inserts [$field] = array(
                'value' => $value,
                'func' => $func
            );
        }
        return $this;
    }

    function delete()
    {
        $this->_type = 'delete';
        return $this;
    }

    /**
     *
     * @param  $field   string
     * @param $alias string
     * @param  $func bool
     * @return DBQuery
     */
    function select($field, $alias = null, $func = false)
    {
        if ($alias) {
            if (in_array($field, $this->_fields, true)) {
                Utils::arrayRemoveValue($this->_fields, $field);
            }
            $this->_fields [$alias] = array(
                'field' => $field,
                'func' => $func
            );
        } else {
            $this->_fields [] = array(
                'field' => $field,
                'func' => $func
            );
        }
        return $this;
    }

    function union($o)
    {
        $this->_unions [] = $o;
    }

    function getFields()
    {
        return $this->_fields;
    }

    /**
     *
     * @param  $f
     * @return DBQuery
     */
    function groupBy($f)
    {
        $this->_groupBy [] = $f;
        return $this;
    }

    function having($f)
    {
        $this->_having [] = $f;
        return $this;
    }

    function addGroupBy($f)
    {
        return $this->groupBy($f);
    }

    protected function getAllField()
    {
        return "*";
    }

    protected function getWhere()
    {
        return $this->_where;
    }

    protected function quoteAlias($fields)
    {
        if (strpos($fields, ',') === false && strpos($fields, '*') === false && strpos($fields, ' ') === false && strpos($fields, '.') === false) {
            return $this->quoteTable($fields);
        }
        return $fields;
    }

    protected function quoteField($fields)
    {
        if ($fields===(array)$fields) {
            $retval = '';
            foreach ($fields as $k => $v) {
                $field = $v;
                $func = false;
                if ($v===(array)$v) {
                    $field = $v ['field'];
                    $func = $v ['func'];
                }
                if (!$func) {
                    $field = $this->quoteField($field);
                }
                if (!is_numeric($k)) {
                    $retval .= $field . " as " . $this->quoteAlias($k) . ",";
                } else {
                    $retval .= $field . ",";
                }
            }
            return substr($retval, 0, strlen($retval) - 1);
        }
        return $this->quoteAlias($fields);
    }

    /**
     * @param $page
     * @param $rowPerPage
     * @return string
     */
    private function getSQLSelect($page = -1, $rowPerPage = -1)
    {
        $prefix = $this->getConnection()->table_prefix;
        $sql = 'select ';
        if ($this->_distinct) {
            $sql .= 'distinct ';
        }
        if (empty ($this->_fields)) {
            $sql .= $this->getAllField();
        } else {
            $sql .= $this->quoteField($this->_fields);
        }
        $sql .= ' from ';
        foreach ($this->_table as $tbl) {
            $sql .= ($tbl ["expr"] ? '' : $prefix) . ($tbl ["expr"] ? $tbl ["_table"] : $this->quoteTable($tbl ["_table"], false)) . ($tbl ["alias"] ? ' as ' . ($tbl ["expr"] ? $this->quoteTable($tbl ["alias"], false) : $tbl ["alias"]) : '') . ",";
        }
        $sql = substr($sql, 0, strlen($sql) - 1);
        if (count($this->_join)) {
            $sql .= " ";
            foreach ($this->_join as $join) {
                $sql .= $join ["type"] . " join " . ($join ["view"] ? '' : $prefix) . $join ["table"] . " as " . $join ["alias"] . " on " . $join ["expr"] . " ";
            }
        }
        $sql .= $this->getSQLWhere();
        if (count($this->_groupBy)) {
            $sql .= " group by " . implode(",", $this->_groupBy);
        }
        if (count($this->_having)) {
            $sql .= " having " . implode(",", $this->_having);
        }
        $sql .= $this->getSQLOrder();
        if ($page >= 0 && $rowPerPage > 0) {
            $start = ($page) * $rowPerPage;
            $sql .= " " . $this->getConnection()->getLimitExpr($start, $rowPerPage);
        }
        if ($this->_unions) {
            foreach ($this->_unions as $union) {
                $sql .= 'union ';
                if ($union instanceof DBQuery) {
                    /** @noinspection PhpUndefinedMethodInspection */
                    $sql .= $union->getSQL();
                } else {
                    $sql .= $union;
                }
            }
        }
        return $sql;
    }

    private function getSQLOrder()
    {
        if (count($this->_orderby) > 0) {
            $ob = array();
            foreach ($this->_orderby as $v) {
                $field = $v ['field'];
                $by = $v ['order'];
                $func = false;
                if (isset ($this->_fields [$field])) {
                    $f = $this->_fields [$field];
                    if (is_array($f)) {
                        $func = $f ['func'];
                    }
                }
                if ($func) {
                    $ob [] = $field . ' ' . $by;
                } else {
                    $ob [] = $this->quoteField($field) . ' ' . $by;
                }
            }
            $retval = " order by " . implode(",", $ob);
            return $retval;
        }
        return null;
    }

    public function getFirstTableName()
    {
        if (!isset ($this->_table [0])) {
            $key = array_keys($this->_table);
            $tbl = $key [0];
        } else {
            $tbl = $this->_table [0] ["_table"];
        }
        return $tbl;
    }

    protected function getFirstTable()
    {
        if (!isset ($this->_table [0])) {
            $key = array_keys($this->_table);
            $tbl = array(
                '_table' => $key [0],
                'alias' => $key [0],
                'expr' => false
            );
        } else {
            $tbl = $this->_table [0];
        }
        return $tbl;
    }

    protected function getTableName($includeDBName = false, $quote = true)
    {
        return $quote ? $this->quoteTable($this->getConnection()->table_prefix . $this->getFirstTableName(), $includeDBName) : $this->getConnection()->table_prefix . $this->getFirstTableName();
    }

    private function getSQLUpdate()
    {
        if (count($this->_table) == 0) {
            throw new \Exception ("Table Not Found");
        }
        if (count($this->_update) == 0) {
            throw new \Exception ("No Field Definition for Update");
        }
        $tbl = $this->getFirstTable();
        // pp($tbl);
        $sql = "update " . $this->quoteTable($this->getConnection()->table_prefix . $tbl ['_table']) . (isset ($tbl ['alias']) ? ' as ' . $this->quoteTable($tbl ['alias'], false) : '') . " set ";
        foreach ($this->_update as $k => $v) {
            $sql .= $this->quotetable($k) . $v [0] . ($v [2] ? $v [1] : $this->quote($v [1])) . ",";
        }
        $sql = substr($sql, 0, strlen($sql) - 1);
        $sql .= $this->getSQLWhere();
        return $sql;
    }

    private function getSQLDelete()
    {
        $sql = "delete from " . $this->getTableName();
        $sql .= $this->getSQLWhere($this->_where);
        return $sql;
    }

    private function getSQLInsert()
    {
        $sql = "insert into " . $this->getTableName() . " (";
        foreach ($this->_inserts as $k => $v) {
            $sql .= $this->quoteTable($k) . ',';
        }
        $sql = substr($sql, 0, strlen($sql) - 1);
        // ppd($sql);
        // $sql .= implode(",", array_keys($this->_inserts));
        $sql .= ") values (";
        $f = '';
        foreach ($this->_inserts as $insert) {
            if ($insert ['func']) {
                $f .= $insert ['value'] . ',';
            } else {
                $f .= $this->quote($insert ['value']) . ',';
            }
        }
        $f = substr($f, 0, strlen($f) - 1);
        $sql .= $f;
        // $sql .= implode(",", $this->_inserts);
        $sql .= ")";
        return $sql;
    }

    public function isObjectExist($objectName, $objectType = "table")
    {
        return $this->getConnection()->isObjectExist($objectName, $objectType);
    }

    private function getSQLDrop()
    {
        if (empty ($this->_drops)) {
            throw new \Exception ("NO Object to drop");
        }
        foreach ($this->_drops as $d) {

            if ($this->isObjectExist($d [1], $d [0])) {

                $this->addSQL("DROP " . strtoupper($d [0]) . " " . $this->getConnection()->table_prefix . $d [1]);
            }
        }
        if (count($this->_sql)) {
            return $this->getSQL();
        }
        return null;
    }

    private function getSQLWhere($where = null)
    {
        if ($where == null) {
            $where = $this->_where;
        }
        $c = count($where);
        if ($c) {
            $r = " where ";
            $idx = 0;
            foreach ($where as $w) {
                $r .= '(' . $w [0] . ')';
                if ($idx < $c - 1) {
                    $r .= " " . $w [1] . " ";
                }
                $idx++;
            }
            return $r;
        }
        return null;
    }

    protected function prepare($type = null)
    {
        $this->_prepared = true;
    }

    function getSQL($page = -1, $rowPerPage = -1)
    {
        if (!$this->_prepared) {
            $this->prepare($this->_type);
        }
        switch (strtolower($this->_type)) {
            case self::MODE_UPDATE :
                return $this->getSQLUpdate();
                break;
            case self::MODE_SELECT :
                return $this->getSQLSelect($page, $rowPerPage);
                break;
            case self::MODE_DIRECT :
                return $this->_sql;
                break;
            case self::MODE_DELETE :
                return $this->getSQLDelete();
            case self::MODE_INSERT :
                return $this->getSQLInsert();
            case self::MODE_DROP :
                return $this->getSQLDrop();
            case self::MODE_CREATE_TABLE :
                return $this->getConnection()->getSQLCreateTable($this);
            default :
                throw new \Exception ("Unknown QueryType " . $this->_type);
                break;
        }
    }

    function loadScalar()
    {
        return ( int )Utils::getObjectProperty($this->loadObject(), 0);
    }

    function loadObject($o = null)
    {
        if ($o === 'this') {
            $o = $this;
        }
        $sql = $this->getSQL(0, 1);
        if (is_array($sql)) {
            $sql = $sql [0];
        }
        $q = $this->exec($sql);
        if (!($q instanceof DBResultList)) {
            Logger::Warning($sql);
        }
        if ($q && $q->count()) {
            if (is_string($o)) {
                $o = new $o ($this);
            }
            $r = $this->prepareOutput(Utils::toObject($q->First(), $o));
            return $r;
        } else {
            if (is_string($o)) {
                $o = new $o ($this);
            }
        }
        return $o ? $o : null;
    }

    protected function prepareOutput($o)
    {
        return $o;
    }

    function loadObjects($class = null, $page = -1, $rowPerPage = -1)
    {
        $sql = $this->getSQL($page, $rowPerPage);
        if (is_array($sql)) {
            $sql = $sql [0];
        }
        $q = $this->exec($sql);
        if ($q) {
            $retval = array();
            while ($r = $q->Next()) {
                if ($r = $this->prepareOutput($r)) {
                    if ($class !== null) {
                        if ($r && is_object($r) && get_class($r) === $class) {
                            $o = $r;
                        } else {
                            $o = new $class ($r);
                        }
                    } else {
                        $o = new \stdClass ();
                        $o = Utils::toObject($r, $o);
                    }
                    $retval [] = $o;
                }
            }
            return $retval;
        }
        return null;
    }

    /**
     * @static
     * @param               $sql
     * @param IDBConnection $connection
     * @return mixed
     */
    public static function execute($sql, IDBConnection $connection)
    {
        return $connection->exec($sql);
    }

    function exec($sql = null)
    {
        if ($sql == null) {
            $sql = $this->getSQL();
        }
        if (empty ($sql)) {
            if ($this->_throwOnError) {
                ppd('empty');
                throw new \Exception ("Empty SQL");
            }
            return false;
        }
        $this->_lastSQL = $sql;
        $res = null;
        try {
            if (is_array($sql)) {
                $res = $this->getConnection()->execBatch($sql);
            } else {
                $res = $this->getConnection()->Query($sql);
            }
        } catch (\Exception $e) {
            if ($this->_throwOnError) {
                throw $e;
            }

            $this->setLastError($e->getMessage());
        }
        return $res;
    }

    function getError()
    {
        return $this->getConnection()->getError();
    }

    /**
     * Add table to statement
     *
     * @param $table string
     * @param $alias string
     * @param $expr  boolean
     * @return \System\DB\DBQuery
     */
    function addTable($table, $alias = null, $expr = false)
    {
        if (!in_array($table, $this->_table)) {
            $this->_table [$table] = array(
                '_table' => $table,
                'alias' => $alias,
                'expr' => $expr
            );
        }
        return $this;
    }

    protected function setJoin($join)
    {
        $this->_join = $join;
    }

    /**
     * @param        $table
     * @param        $alias
     * @param        $expr
     * @param        $m  string
     * @param        $vw bool
     * @return DBQuery
     */
    function join($table, $alias, $expr, $m = "inner", $vw = false)
    {
        $this->_join [] = array(
            "table" => $table,
            "alias" => $alias,
            "expr" => $expr,
            "type" => $m,
            'view' => $vw
        );
        return $this;
    }

    function leftJoin($table, $alias, $expr, $vw = false)
    {
        return $this->join($table, $alias, $expr, 'left', $vw);
    }

    /**
     * Enter description here .
     * ..
     *
     * @param $table string
     * @param $alias string
     * @param $expr  boolean
     * @param $m     string
     * @return DBQuery
     * @deprecated please use join
     */
    function addJoin($table, $alias, $expr, $m = "inner")
    {
        return $this->join($table, $alias, $expr, $m);
    }

    function drop($object = null, $what = "table")
    {
        if ($object) {
            $this->clear();
            $this->_drops [] = array(
                $what,
                $object
            );
            $this->_type = self::MODE_DROP;
        }
        return $this;
    }
}

?>

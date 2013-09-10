<?php
namespace System\DB\Adapters;

use System\DB\DBConnection as DBConnection;
use System\DB\DBException;
use System\DB\DBFieldInfo;
use System\DB\DBQuery;
use System\DB\DBReflectionClass;
use System\DB\DBResultList;
use System\DB\Table;

class DBSQLiteAdapter extends DBConnection
{
    private $_objects = array();
    private $_affectedRow = 0;

    /**
     * @param array $connArgs
     */
    function __construct($connArgs)
    {
        parent::__construct($connArgs);
        if (!extension_loaded("sqlite")) {
            \System::loadExtension('sqlite');
        }
    }

    /**
     * @param $str
     * @param bool $prep
     * @return string
     */
    function quote($str, $prep = true)
    {
        return ($prep ? "'" : "") . sqlite_escape_string($str)
        . ($prep ? "'" : "");
    }

    protected function first(&$r)
    {
    }

    /**
     * @param $sql
     * @return null|\System\DB\DBResultList
     */
    function Query($sql)
    {
        $sql = $this->prepareQuery($sql);
        $this->Log($sql);
        /** @noinspection PhpPassByRefInspection */
        $this->_result = @sqlite_query($sql, $this->_resource, SQLITE_ASSOC,
            $this->_lastError);
        if ($this->_result == false) {
            $this
                ->throwError(
                    new DBException(
                        $this->_lastError
                        . \Logger::WriteDebug($sql)));
        }
        $this->_affectedRow = sqlite_changes($this->_resource);
        return $this->toResultList();
    }

    function fetchAssoc()
    {
        if ($this->_result) {
            return sqlite_fetch_array($this->_result, true);
        }
        return null;
    }

    function fetchObject()
    {
        if (is_resource($this->_result)) {
            $arr = sqlite_fetch_array($this->_result, true);
            if (!$arr) {
                return null;
            }
            $obj = new \stdClass();
            foreach ($arr as $key => $value) {
                $obj->$key = $value;
            }
            return $obj;
        }
        return null;
    }

    function isObjectExist($objectName, $objectType)
    {
        $objectName = $this->toTableName($objectName);
        if (!isset($this->_objects[$objectType])) {
            $this->_objects[$objectType] = array();
            $r = $this
                ->Query(
                    "select * from sqlite_master where type=\"$objectType\"");
            while ($row = $r->next()) {
                /** @var $row mixed */
                $this->_objects[$row->type][$row->tbl_name] = true;
            }
        }
        return isset($this->_objects[$objectType][$objectName]) ? $this
            ->_objects[$objectType][$objectName] : false;
    }

    function exec($sql)
    {
        $sql = $this->prepareQuery($sql);
        if (\Strings::BeginWith($sql, 'drop', false)
            || \Strings::BeginWith($sql, 'create', false)
        ) {
            $this->_objects = array();
        }
        $this->Log($sql);
        $this->_result = @sqlite_exec($this->_resource, $sql, $this->_lastError);
        if ($this->_result == false) {
            $this->throwError(new DBException($this->_lastError));
        }
        $this->_affectedRow = sqlite_changes($this->_resource);
        return $this->toResultList();
    }

    function Open()
    {
        if ($this->isConnected()) {
            return true;
        }
        $host = \Utils::ToDirectory($this->getArg("host"));
        if (!$host) {
            $host = \CGAF::getInternalStorage('db', false);
        }
        $host = realpath(\Utils::ToDirectory($host));
        if (!$host) {
            \Logger::WriteDebug($host);
            throw new DBException('Sqlite storage not found');
        }
        $file = $host . DS . $this->getArg('database') . '.sqlite';
        $this->_lastError = null;
        if (!is_file($file) && $this->getArg('autocreate', CGAF_DEBUG)) {
            $this->_resource = sqlite_popen($file, 0666, $this->_lastError);
        } else {
            $this->_resource = sqlite_open($file, 0666, $this->_lastError);
        }
        if ($this->_resource === false) {
            $lerror = \CGAF::getLastError();
            throw new DBException($lerror);
        }
        $this->SelectDB($file);
        $this->setConnected($this->_resource != false);
        return $this->isConnected();
    }

    function getObjectInfo($objectId, $objectType = "table", $throw = true)
    {
        $retval = array();
        if (!$this->isObjectExist($objectId, $objectType)) {
            return null;
        }
        switch ($objectType) {
            case 'table':
                $objectId = $this->toTableName($objectId);
                $r = sqlite_fetch_column_types($objectId, $this->_resource);
                foreach ($r as $c => $t) {
                    $o = new SQLiteFieldInfo($this);
                    $ftype = $t;
                    if (strpos($t, "(") > 0) {
                        $ftype = substr($t, 0, strpos($t, "("));
                    }
                    $o->field_width = (int)substr($t, strpos($t, "(") + 1,
                        strpos($t, ")"));
                    $o->field_name = $c;
                    $o->field_type = $ftype;
                    $retval[$c] = $o;
                }
        }
        return $retval;
    }

    function getError()
    {
        return $this->_lastError;
    }

    function getLimitExpr($start, $end)
    {
        return "LIMIT $start,$end";
    }

    function parseFieldCreate($name, $type, $width, $args = null)
    {
        $retval = "$name ";
        switch (strtolower($type)) {
            case 'date':
            case 'datetime':
                $width = null;
                break;
        }
        $type = $this->phpToDBType($type);
        return "$retval $type" . ($width !== null ? " ($width)" : "") . " "
        . $args;
    }

    public function createDBObjectFromClass($classInstance, $objecttype,
                                            $objectName)
    {
        $r = new DBReflectionClass($classInstance);
        $fields = $r->getFields();
        //$this->Exec('drop ' .$objecttype . ' #__'.$this->quoteTable($objectName));
        $sql = 'create ' . $objecttype . ' #__'
            . $this->quoteTable($objectName) . ' (';
        $fdefs = array();
        foreach ($fields as $fieldName => $fieldDefs) {
            if (!$fieldDefs) {
                ppd($fieldDefs);
            }
            $s = $this->quoteTable($fieldDefs->fieldName) . ' ';
            //$this->phpToDBType($fieldDefs->FieldType)
            $ftype = trim(strtolower($fieldDefs->FieldType));
            $length = $fieldDefs->fieldlength;
            $default = $fieldDefs->FieldDefaultValue;
            switch (($ftype)) {
                case 'datetime':
                    if (strtolower($default) == 'current_timestamp') {
                        $default = "(strftime('%Y-%m-%dT%H:%M','now', 'localtime'))";
                    }
                    $length = null;
                    break;
                case 'boolean':
                case 'int':
                case 'integer':
                case 'date':
                    $length = null;
                    break;
                case 'varchar':
                case 'text':
                    break;
                case '':
                    $ftype = 'varchar';
                    break;
                default:
                    ppd($fieldDefs->FieldType);
                    break;
            }
            $s .= $ftype;
            if ($length !== null) {
                $s .= ' (' . $fieldDefs->fieldlength . ')';
            }
            if ($fieldDefs->FieldIsPrimaryKey) {
                $s .= ' primary key';
            }
            if ($default) {
                $s .= ' default ' . $default;
            }
            $fdefs[] = $s;
        }
        $sql .= implode(',', $fdefs);
        $sql .= ')';
        return $this->Exec($sql);
    }

    /**
     *
     */

    public function getLastInsertId()
    {
    }

    /**
     *
     */

    public function getAffectedRow()
    {
    }

    private function phpToDBType($type)
    {
        switch (strtolower($type)) {
            case "smallint":
                break;
            case "boolean":
                break;
            case 'int':
            case 'double':
            case "tinyint":
            case "integer":
                $type = 'numeric';
                break;
            case "string":
            case "varchar":
                $type = "varchar";
                break;
            case 'timestamp':
            case "datetime":
                $type = 'integer';
                break;
            case 'text':
                break;
            default:
                throw new DBException("unknown type $type for database mysql");
        }
        return $type;
    }

    public function getFieldConfig($fieldType = null)
    {
        $fieldType = $this->phpToDBType($fieldType);
        $def = array(
            'DefaultFieldLength' => 20
        );
        $retval = array(
            'int' => array(
                'DefaultFieldLength' => 11
            )
        );
        if ($fieldType === null) {
            return $retval;
        }
        return isset($retval[$fieldType]) ? $retval[$fieldType] : $def;
    }

    /**
     * @param Table|DBQuery $q
     * @return string
     */
    function getSQLCreateTable($q)
    {
        $retval = "create table " . $this->getArg("table_prefix")
            . $q->getFirstTableName() . " ";
        $retval .= "(" . implode($q->getFields(), ",");
        $pk = $q->getPK();
        if ($pk) {
            $retval .= ',PRIMARY KEY (' . $this->quoteTable($pk) . ')';
        }
        $retval .= ")";
        ppd($retval);
        //$retval .= ' ENGINE = ' . $q->getAppOwner ()->getConfig ( 'db.table_engine', 'InnoDB' );
        //$retval .= $this->getSQLParams();
        return $retval;
    }

    /* (non-PHPdoc)
     * @see \System\DB\IDBConnection::fieldTypeToPHP()
     */

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

    /**
     * @return DBResultList
     */
    function getTableList()
    {
        // TODO: Implement getTableList() method.
    }
}

class SQLiteFieldInfo extends DBFieldInfo
{

    function quoteValueForField($field, &$value = null)
    {
        switch ($field) {
            case 'boolean':
                if ((boolean)$value) {
                    $value = 1;
                } else {
                    $value = 0;
                }
                break;
            case "varchar":
            default:
        }
        return true;
    }
}

?>

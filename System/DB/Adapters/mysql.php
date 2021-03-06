<?php
namespace System\DB\Adapters;

use DateUtils;
use Exception;
use System\ACL\ACLHelper;
use System\DB\DB;
use System\DB\DBConnection;
use System\DB\DBException;
use System\DB\DBFieldDefs;
use System\DB\DBFieldInfo;
use System\DB\DBIndexInfo;
use System\DB\DBQuery;
use System\DB\DBReflectionClass;
use System\DB\Table;
use System\Exceptions\SystemException;

class MySQL extends DBConnection
{
    private $_affectedRow = 0;
    private $_engine = "innoDb";
    private $_defaultFieldLength = array(
        'varchar' => 50
    );

    function __construct($connArgs)
    {
        if (!function_exists('mysql_connect')) {
            if (!\System::loadExtenstion('mysql')) {
                throw new DBException('mysql not installed');
            }
        }
        parent::__construct($connArgs);
    }

    function Open()
    {
        parent::Open();
        if ($this->isConnected()) {
            return true;
        }
        $this->_resource = @mysql_connect($this->getArg("host"),
            $this->getArg("username"), $this->getArg("password"),
            $this->getArg("persist", true));
        if (!$this->_resource) {
            $this->_lastError = mysql_error();
        }
        if ($this->_resource === false) {
            throw new Exception($this->_lastError);
        }
        if ($this->getArg("database") !== null) {
            $this->SelectDB($this->getArg("database"));
        }
        $this->setConnected($this->_resource != false);
        return $this->isConnected();
    }

    function Close()
    {
        if ($this->_resource) {
            mysql_close($this->_resource);
        }
        $this->_resource = null;
        parent::Close();
    }

    public function createDBObjectFromClass($classInstance, $objecttype,
                                            $objectName)
    {
        $r = new DBReflectionClass($classInstance);
        $fields = $r->getFields();
        $ref = array();
        //$objectName.='_test';
        switch (strtolower($objecttype)) {
            case 'table':
                $retval = array();
                $retval[] = "create table "
                    . $this->quoteTable('#__' . $objectName) . " (";
                $idx = 0;
                if (!$fields) throw new DBException('create table error, empty object properties');
                /**
                 * @var DBFieldDefs | \stdClass $field
                 */
                foreach ($fields as $field) {

                    $type = $this->phptofieldtype($field->fieldtype);
                    $fcreate = $this->quoteTable($field->fieldname, false)
                        . ' ' . $type;
                    if ($field->fieldlength != null) {
                        $fcreate .= '(' . $field->fieldlength . ')';
                    } elseif (isset(
                    $this->_defaultFieldLength[strtolower($type)])
                    ) {
                        $fcreate .= '('
                            . $this->_defaultFieldLength[strtolower($type)]
                            . ')';
                    }
                    if ($field->isAllowNull() === false) {
                        $fcreate .= ' NOT NULL';
                    }
                    if ($field->fielddefaultvalue !== null) {
                        $fcreate .= ' DEFAULT '
                            . $this
                                ->parseDefaultValue(
                                    $field->fielddefaultvalue,
                                    $field->fieldtype);
                    }
                    if ($field->isAutoIncrement()) {
                        $fcreate .= ' AUTO_INCREMENT';
                    }
                    $retval[] = $fcreate . ',';
                    if ($field->FieldReference) {
                        $fr = explode(' ', trim($field->FieldReference));
                        $ref[] = array(
                            'field' => $field->fieldname,
                            'id' => $field->FieldReferenceId ? $field
                                ->FieldReferenceId
                                : 'fk_' . $objectName . '_' . $idx,
                            'reftable' => $fr[0],
                            'reffield' => $fr[1],
                            'delete' => isset($fr[2]) ? $fr[2] : 'no',
                            'update' => isset($fr[3]) ? $fr[3] : 'no'
                        );
                    }
                    $idx++;
                }
                //$retval = substr ( $retval, 0, strlen ( $retval ) - 1 );
                $clast = $retval[count($retval) - 1];
                $retval[count($retval) - 1] = substr($clast, 0,
                    strlen($clast) - 1);
                $pk = $r->getPrimaryKey();
                if ($pk) {
                    $retval[] = ',PRIMARY KEY (' . $this->quoteTable($pk) . ')';
                }
                if ($ref) {
                    foreach ($ref as $v) {
                        $retval[] = ',KEY ' . $this->quoteTable($v['id']) . '('
                            . $this->quoteTable($v['field']) . ')';
                        $retval[] = ',CONSTRAINT '
                            . $this->quoteTable($v['id'])
                            . ' FOREIGN KEY ('
                            . $this->quoteTable($v['field']) . ')'
                            . ' REFERENCES '
                            . $this->quoteTable($v['reftable']) . ' ('
                            . $this->quoteTable($v['reffield']) . ')'
                            . ' ON DELETE '
                            . ($v['delete'] === 'no' ? 'NO ACTION'
                                : 'CASCADE') . ' ON UPDATE '
                            . ($v['update'] === 'no' ? 'NO ACTION'
                                : 'CASCADE');
                    }
                }
                $retval[] = ')  ENGINE = '
                    . $this->getArg('table_engine', 'InnoDB');
                break;
            default:
                throw new Exception($objecttype);
        }
        $this->_thows = true;
        return $this->Exec(implode('', $retval));
    }

    private function parseDefaultValue($val, $type = null)
    {
        switch ($val) {
            case '#CURRENT_USER#':
                $val = ACLHelper::getUserId();
                break;
            case 'PHP_EOL':
                return $this->quote(PHP_EOL);
            default:
                switch ($type) {
                    case 'string':
                    case 'text':
                    case 'varchar':
                        return $this->quote($val);
                }
        }
        return $val;
    }

    function phptofieldtype($type, $rec = true)
    {
        $r = 'varchar';
        switch (strtolower($type)) {
            case null:
            case 'unknown_type':
            case 'varchar':
            case 'string':
                $r = 'varchar';
                break;
            case 'int':
            case 'integer':
                $r = 'int';
                break;
            case 'smallint':
                $r = 'smallint';
                break;
            case 'date':
            case 'datetime':
                $r = 'datetime';
                break;
            case 'boolean':
                $r = 'bit';
                break;
            case 'text':
                $r = $type;
                break;
            case 'timestamp':
                $r = $type;
                break;
            default:
                if (!$rec) {
                    ppd($type);
                    return 'varchar';
                }
                // contains comment ?
                $t = explode(' ', $type);
                if (count($t) > 1) {
                    $t = array_shift($t);
                    return $this->phptofieldtype($t, false);
                }
        }
        return $r;
    }

    /**
     * @param $q Table
     * @return string
     */
    function getSQLCreateTable($q)
    {
        $retval = "create table " . $this->table_prefix
            . $q->getFirstTableName() . " ";
        $retval .= "(" . implode($q->getFields(), ",");
        $pk = $q->getPK();
        if ($pk) {
            $retval .= ', /* Keys */';
            $retval .= ' PRIMARY KEY (' . $this->quoteTable($pk) . ')';
        }
        $retval .= ")";
        $retval .= ' ENGINE = '
            . $q->getAppOwner()->getConfig('db.table_engine', 'InnoDB');
        // $retval .= $this->getSQLParams();
        return $retval;
    }

    function emptyDate()
    {
        return '0000-00-00 00:00:00';
    }

    public function getFieldConfig($fieldType = null)
    {
        $fieldType = self::parseFieldType($fieldType);
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

    public function isEmptyDate($d)
    {
        return empty($d) || $d === $this->emptyDate();
    }

    public function DateToUnixTime($mysql_timestamp)
    {
        return DateUtils::DateToUnixTime($mysql_timestamp);
    }

    public function timeStamp()
    {
        return 'CURRENT_TIMESTAMP';
    }

    /**
     * @param null $date
     * @return string
     */
    public function DateToDB($date = null)
    {
        $dt = new \CDate($date);
        return $date ? $dt->format(DATE_ISO8601) : \CDate::Current();
    }

    function fieldTypeToPHP($type)
    {
        $retval = "String";
        switch (strtolower($type)) {
            case 'text':
            case 'varchar':
                $retval = 'String';
                break;
            case 'smallint':
                $retval = $type;
                break;
            case 'int':
                $retval = 'int';
                break;
            case 'blob':
                $retval = 'text';
                break;
            case 'timestamp':
                $retval = 'datetime';
                break;
            default:
                ppd($type);
        }
        return $retval;
    }

    /**
     * @param $db
     * @param bool $create
     * @return DBConnection |void
     */
    function SelectDB($db, $create = true)
    {
        $this->_database = $db;
        if (mysql_select_db($db, $this->_resource)) {
            return $this;
        } elseif ($create) {
            if ($this->createDB($db)) {

                return $this->SelectDB($db, false);
            }

        }
        $this->throwError(new \Exception(mysql_error()));
        return $this;
    }

    protected function createDB($db)
    {

        if (mysql_query('CREATE DATABASE ' . $db, $this->_resource)) {
            return true;
        }

        return false;
    }

    function quote($str, $prep = true)
    {
        $str = mysql_real_escape_string($str);
        $str = addcslashes($str, "%");
        return ($prep ? "'" : "") . $str . ($prep ? "'" : "");
    }

    function quoteTable($table, $includedbname = false)
    {
        if ($table===(array)$table) {
            $retval = '';
            foreach ($table as $t) {
                $retval .= $this->quoteTable($t, $includedbname) . ',';
            }
            $retval = substr($retval, 0, strlen($retval) - 1);
            return $retval;
        }
        $retval = "`$table`";
        if ($includedbname) {
            $retval = '`' . $this->getArg('database') . "`.`"
                . $this->getArg('table_prefix', "") . "$table`";
        }
        return $retval;
    }

    function fetchObject()
    {
        if (is_resource($this->_result)) {
            return @mysql_fetch_object($this->_result);
        }
        return null;
    }

    protected function unQuoteField($field)
    {
        return str_replace('`', '', $field);
    }

    function fetchAssoc()
    {
        return mysql_fetch_assoc($this->_result);
    }

    function getAffectedRow()
    {
        return mysql_affected_rows($this->_resource);
    }

    function isObjectExist($objectName, $objectType)
    {
        $this->Open();
        switch ($objectType) {
            case "table":
                $objectName = str_replace('`', '', $objectName);
                $sql = "select * from information_schema.TABLES where TABLE_NAME='"
                    . $this->getArg('table_prefix', "") . $objectName
                    . "' and TABLE_SCHEMA='" . $this->_database . "'";
                $rs = $this->Query($sql);
                return $rs->count();
                break;
        }
        return false;
    }

    function getTableList()
    {
        $this->Open();
        $sql = "select table_name,table_type from information_schema.TABLES where table_schema='"
            . $this->_database
            . "' and (table_type='BASE_TABLE' or table_type='BASE TABLE')";
        return $this->exec($sql);
    }

    function getIndexes($table)
    {
        $q = $this->Query('show indexes in ' . $table);
        $retval = array();
        /**
         * @var \stdClass $r
         */
        while ($r = $q->next()) {
            $o = new DBIndexInfo();
            $o->Table = $r->Table;
            $o->Title = $r->Key_name;
            $o->Column = $r->Column_name;
            $o->Type = $r->Index_type;
            $o->Comment = $r->Index_Comment;
            $o->Unique = $r->Non_unique == 0;
            /*
             * [Table] => exim [Non_unique] => 0 [Key_name] => PRIMARY
             * [Seq_in_index] => 1 [Column_name] => id [Collation] => A
             * [Cardinality] => 1 [Sub_part] => [Packed] => [Null] =>
             * [Index_type] => BTREE [Comment] => [Index_Comment] =>
             */
            $retval[] = $o;
        }
        return $retval;
    }

    function exec($sql, $fetchMode = DB::DB_FETCH_OBJECT)
    {
        return $this->Query($sql, $fetchMode);
    }

    function getError()
    {
        return mysql_error($this->_resource);
    }

    function getObjectInfo($objectId, $objectType = "table", $throw = true)
    {
        $retval = array();
        switch ($objectType) {
            case "table":
                $sql = "desc " . $this->quoteTable($objectId, true);
                $old = $this->_thows;
                $this->_thows = $throw;
                $r = $this->Query($sql);
                if (!$r) {
                    return null;
                }
                $this->_thows = $old;
                $reff = $this
                    ->Query(
                        'select
table_schema
, table_name
, column_name
, referenced_table_schema
, referenced_table_name
, referenced_column_name
, constraint_name						
from information_schema.KEY_COLUMN_USAGE
where table_name = ' . $this->quote($objectId) . ' and table_schema='
                        . $this
                            ->quote(
                                $this
                                    ->getArg(
                                        "database"))
                        . ' and not referenced_table_name is null');
                $refftable = array();
                /**
                 * @var \stdClass $row
                 */
                while ($row = $reff->next()) {
                    $refftable[$row->column_name] = array(
                        'schhema' => $row->referenced_table_schema,
                        'table' => $row->referenced_table_name,
                        'field' => $row->referenced_column_name,
                        'id' => $row->constraint_name
                    );
                }
                while ($row = $r->next()) {
                    $o = new MYSQLFieldInfo($this);
                    $ftype = $row->Type;
                    if (strpos($row->Type, "(") > 0) {
                        $ftype = substr($row->Type, 0, strpos($row->Type, "("));
                    }
                    $o->field_name = $row->Field;
                    if (isset($refftable[$o->field_name])) {
                        $o->reference = $refftable[$o->field_name];
                    }
                    $o->field_type = $ftype;
                    $t = substr($row->Type, strpos($row->Type, "(") + 1);
                    $o->field_width = (int)substr($t, 0, strpos($t, ")"));
                    $o->allow_null = $row->Null == "YES"
                        || ($row->Extra ? strpos("auto_increment",
                                $row->Extra) >= 0 : false);
                    $o->primary = isset($row->PRI) ? $row->PRI
                        : (isset($row->Key) ? $row->Key === 'PRI' : false);
                    $o->extra = $row->Extra;
                    $o->default_value = $row->Default;
                    if ($row->Extra == "auto_increment") {
                        $o->primary = true;
                    }
                    $retval[$row->Field] = $o;
                }
                break;
        }
        return $retval;
    }

    public function parseFieldType($type)
    {
        switch (strtolower($type)) {
            case "boolean":
                break;
            case 'int':
            case 'double':
            case "smallint":
            case "tinyint":
            case "integer":
                break;
            case "string":
            case "varchar":
                $type = "varchar";
                break;
            case 'timestamp':
                $type = 'timestamp';
                break;
            case "datetime":
            case 'text':
                break;
            default:
                throw new SystemException(
                    "unknown type $type for database mysql");
        }
        return $type;
    }

    function parseFieldCreate($name, $type, $width, $args = null)
    {
        $retval = "$name ";
        switch (strtolower($type)) {
            case "boolean":
                $type = "boolean";
                $width = null;
                break;
            case "int":
            case 'double':
            case "smallint":
            case "tinyint":
            case "integer":
                break;
            case "string":
            case "varchar":
                $type = "varchar";
                break;
            case 'timestamp':
                $type = 'timestamp';
                $width = null;
                break;
            /** @noinspection PhpMissingBreakStatementInspection */
            case "datetime":
                $width = null;
            case 'text':
                break;
            default:
                throw new SystemException(
                    "unknown type $type for database mysql");
        }
        return "$retval $type" . ($width !== null ? " ($width)" : "") . " "
        . $args;
    }

    function Query($sql, $fetchMode = DB::DB_FETCH_OBJECT)
    {
        $this->Open();


        $sql = $this->prepareQuery($sql);
        $this->setLastSQL($sql);
        $this->Log($sql);
        $cache = $this->getCached($sql);
        if ($cache) return $cache;
        $this->_result = @mysql_query($sql, $this->_resource);
        if ($this->_result == false) {
            $err = mysql_error($this->_resource);
            $this->throwError(new \Exception($err), $sql);
            return null;
        }
        return $this->toResultList();
    }

    function getLimitExpr($start, $end)
    {
        return "LIMIT $start,$end";
    }

    function getLastInsertId()
    {
        return mysql_insert_id($this->_resource);
    }

    function getDatabases()
    {
        $o = $this->Exec('show databases');
        $retval = array();
        /**
         * @var \stdClass $r
         */
        while ($r = $o->next()) {
            $retval[] = $r->Database;
        }
        return $retval;
    }

    public function dropDB()
    {
        if (CGAF_DEBUG) {
            $this->exec('drop database ' . $this->getArg('database'));
            $this->Close();
        }

    }
}

class MYSQLFieldInfo extends DBFieldInfo
{

    function quoteValueForField($field, &$value = null)
    {
        switch (strtolower($field)) {
            case 'text':
            case "varchar":
                return true;
            case "date":
            case "datetime":
                if ($value === null) {
                    $value = DateUtils::now('Y-m-d H:i:s');
                } else if ($value == '0000-00-00 00:00:00') {
                    return true;
                }
                if ($value == 'CURRENT_TIMESTAMP') {
                    return false;
                }
                $date = DateUtils::toDate($value);
                $value = $date->format(FMT_DATETIME_MYSQL);
                return true;
            case 'timestamp':
                if ($value == 'CURRENT_TIMESTAMP') {
                    return false;
                }
                $date = \DateUtils::toDate($value);
                $value = $date->format(FMT_DATETIME_MYSQL);
                return true;
            case "int":
            case "smallint":
            case "tinyint":
                if ($value === null) {
                    return true;
                }
                return false;
        }
        return true;
    }

    function getPHPType()
    {
        return $this->_connection->fieldTypeToPHP($this->field_type);
    }
}

?>

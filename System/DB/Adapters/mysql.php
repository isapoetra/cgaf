<?php
namespace System\DB\Adapters;
use System\DB\DB;
use \String;
use System\DB\DBConnection;
use System\DB\DBFieldInfo;
use \DateUtils;
use System\DB\DBReflectionClass;
use System\DB\IndexInfo;
use System\Exceptions\SystemException;
class MySQL extends DBConnection {
	private $_affectedRow = 0;
	private $_engine = "innoDb";
	private $_defaultFieldLength = array(
			'varchar' => 50);
	function Open() {
		parent::Open();
		if ($this->isConnected()) {
			return true;
		}
		//ppd($this->getArgs());
		$this->_resource = mysql_connect($this->getArg("host", "localhost"), $this->getArg("username", "root"), $this->getArg("password"), $this->getArg("persist", true));
		if (!$this->_resource) {
			$this->_lastError = mysql_error();
		}
		if ($this->_resource === false) {
			throw new \Exception($this->_lastError);
		}
		if ($this->getArg("database") !== null) {
			$this->SelectDB($this->getArg("database"));
		}
		$this->setConnected($this->_resource != false);
		return $this->isConnected();
	}
	public function createDBObjectFromClass($classInstance, $objecttype, $objectName) {
		$r = new DBReflectionClass($classInstance);
		$fields = $r->getFields();
		switch (strtolower($objecttype)) {
		case 'table':
			$retval = "create table " . $this->quoteTable('#__' . $objectName) . " ";
			$retval .= '(';
			foreach ($fields as $field) {
				$type = $this->phptofieldtype($field->fieldtype);
				$retval .= $field->fieldname . ' ' . $type;
				if ($field->fieldlength != null) {
					$retval .= '(' . $field->fieldlength . ')';
				} elseif (isset($this->_defaultFieldLength[strtolower($type)])) {
					$retval .= '(' . $this->_defaultFieldLength[strtolower($type)] . ')';
				}
				if ($field->isAllowNull() === false) {
					$retval .= ' NOT NULL';
				}
				if ($field->fielddefaultvalue) {
					$retval .= ' DEFAULT ' . $field->fielddefaultvalue;
				}
				$retval .= ',';
			}
			$retval = substr($retval, 0, strlen($retval) - 1);
			$pk = $r->getPrimaryKey();
			if ($pk) {
				$retval .= ', /* Keys */';
				$retval .= ' PRIMARY KEY (' . $this->quoteTable($pk) . ')';
			}
			$retval .= ")";
			$retval .= ' ENGINE = ' . $this->getArg('table_engine', 'InnoDB');
			break;
		default:
			throw new Exception($objecttype);
		}
		//ppd($fields);
		$this->_thows = true;
		return $this->Exec($retval);
	}
	function phptofieldtype($type) {
		switch (strtolower($type)) {
		case 'varchar':
		case 'string':
			return 'varchar';
		case 'datetime':
		case 'int':
			return $type;
		default:
			return 'varchar';
		}
		ppd($type);
	}
	function getSQLCreateTable($q) {
		$retval = "create table " . $this->table_prefix . $q->getFirstTableName() . " ";
		$retval .= "(" . implode($q->getFields(), ",");
		$pk = $q->getPK();
		if ($pk) {
			$retval .= ', /* Keys */';
			$retval .= ' PRIMARY KEY (' . $this->quoteTable($pk) . ')';
		}
		$retval .= ")";
		$retval .= ' ENGINE = ' . $q->getAppOwner()->getConfig('db.table_engine', 'InnoDB');
		//$retval .= $this->getSQLParams();
		return $retval;
	}
	function emptyDate() {
		return '0000-00-00 00:00:00';
	}
	public function getFieldConfig($fieldType = null) {
		$fieldType = self::parseFieldType($fieldType);
		$def = array(
				'DefaultFieldLength' => 20);
		$retval = array(
				'int' => array(
						'DefaultFieldLength' => 11));
		if ($fieldType === null) {
			return $retval;
		}
		return isset($retval[$fieldType]) ? $retval[$fieldType] : $def;
	}
	public function isEmptyDate($d) {
		return empty($d) || $d === $this->emptyDate();
	}
	public function DateToUnixTime($mysql_timestamp) {
		return DateUtils::DateToUnixTime($mysql_timestamp);
	}
	public function timeStamp() {
		return 'CURRENT_TIMESTAMP';
	}
	public function DateToDB($date = null) {
		$dt = new \CDate($date);
		return $dt->format(FMT_DATETIME_MYSQL);
	}
	function SelectDB($db, $create = true) {
		$this->_database = $db;
		if (mysql_select_db($db, $this->_resource)) {
			return $this;
		} elseif ($create) {
			if ($this->createDB($db)) {
				return $this->SelectDB($db, false);
			}
		}
		$this->throwError(new \Exception(mysql_error()));
	}
	protected function createDB($db) {
		if (mysql_query('CREATE DATABASE ' . $db, $this->_resource)) {
			return true;
		}
		return false;
	}
	function quote($str, $prep = true) {
		return ($prep ? "'" : "") . mysql_real_escape_string($str) . ($prep ? "'" : "");
	}
	function quoteTable($table, $includedbname = false) {
		if (is_array($table)) {
			$retval = '';
			foreach ($table as $t) {
				$retval .= $this->quoteTable($t, $includedbname) . ',';
			}
			$retval = substr($retval, 0, strlen($retval) - 1);
			return $retval;
		}
		$retval = "`$table`";
		if ($includedbname) {
			$retval = '`' . $this->getArg('database') . "`.`" . $this->getArg('table_prefix', "") . "$table`";
		}
		return $retval;
	}
	function fetchObject() {
		if (is_resource($this->_result)) {
			return @mysql_fetch_object($this->_result);
		}
		return null;
	}
	protected function unQuoteField($field) {
		return str_replace('`', '', $field);
	}
	function fetchAssoc() {
		return mysql_fetch_assoc($this->_result);
	}
	function getAffectedRow() {
		return mysql_affected_rows($this->_resource);
	}
	function isObjectExist($objectName, $objectType) {
		$this->Open();
		switch ($objectType) {
		case "table":
			$sql = "select * from information_schema.TABLES where TABLE_NAME='" . $this->getArg('table_prefix', "") . $objectName . "' and TABLE_SCHEMA='" . $this->_database . "'";
			$rs = $this->Query($sql);
			return $rs->count();
			break;
		}
		return false;
	}
	function getTableList() {
		$this->Open();
		$sql = "select table_name,table_type from information_schema.TABLES where table_schema='" . $this->_database . "'";
		return $this->exec($sql);
	}
	function getIndexes($table) {
		$q = $this->Query('show indexes in ' . $table);
		$retval = array();
		while ($r = $q->next()) {
			$o = new IndexInfo();
			$o->Table = $r->Table;
			$o->Title = $r->Key_name;
			$o->Column = $r->Column_name;
			$o->Type = $r->Index_type;
			$o->Comment = $r->Index_Comment;
			$o->Unique = $r->Non_unique == 0;
			/* [Table] => exim
			 [Non_unique] => 0
			[Key_name] => PRIMARY
			[Seq_in_index] => 1
			[Column_name] => id
			[Collation] => A
			[Cardinality] => 1
			[Sub_part] =>
			[Packed] =>
			[Null] =>
			[Index_type] => BTREE
			[Comment] =>
			[Index_Comment] => */
			$retval[] = $o;
		}
		return $retval;
	}
	function Exec($sql, $fetchMode = DB::DB_FETCH_OBJECT) {
		return $this->Query($sql, $fetchMode);
	}
	function getError() {
		return mysql_error($this->_resource);
	}
	function getObjectInfo($objectId, $objectType = "table", $throw = true) {
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
			while ($row = $r->next()) {
				$o = new MYSQLFieldInfo($this);
				$ftype = $row->Type;
				if (strpos($row->Type, "(") > 0) {
					$ftype = substr($row->Type, 0, strpos($row->Type, "("));
				}
				$o->field_name = $row->Field;
				$o->field_type = $ftype;
				$t = substr($row->Type, strpos($row->Type, "(") + 1);
				$o->field_width = (int) substr($t, 0, strpos($t, ")"));
				$o->allow_null = $row->Null == "YES" || ($row->Extra ? strpos("auto_increment", $row->Extra) >= 0 : false);
				$o->primary = isset($row->PRI) ? $row->PRI : (isset($row->Key) ? $row->Key === 'PRI' : false);
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
	public function parseFieldType($type) {
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
			throw new SystemException("unknown type $type for database mysql");
		}
		return $type;
	}
	function parseFieldCreate($name, $type, $width, $args = null) {
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
		case "datetime":
			$width = null;
		case 'text':
			break;
		default:
			throw new SystemException("unknown type $type for database mysql");
		}
		return "$retval $type" . ($width !== null ? " ($width)" : "") . " " . $args;
	}
	function Query($sql, $fetchMode = DB::DB_FETCH_OBJECT) {
		$this->Open();
		$sql = $this->prepareQuery($sql);
		$this->setLastSQL($sql);
		$this->Log($sql);
		$this->_result = @mysql_query($sql, $this->_resource);
		if ($this->_result == false) {
			$err = mysql_error($this->_resource);
			$this->throwError(new \Exception($err), $sql);
			return null;
		}
		return $this->toResultList();
	}
	function getLimitExpr($start, $end) {
		return "LIMIT $start,$end";
	}
	function getLastInsertId() {
		return mysql_insert_id($this->_resource);
	}
	function getDatabases() {
		$o = $this->Exec('show databases');
		$retval = array();
		while ($r = $o->next()) {
			$retval[] = $r->Database;
		}
		return $retval;
	}
}
class MYSQLFieldInfo extends DBFieldInfo {
	function quoteValueForField($field, &$value = null) {
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
	function getPHPType() {
		$retval = "String";
		switch (strtolower($this->field_type)) {
		case "int":
			$retval = "int";
			break;
		case "blob":
			$retval = "text";
		}
		return $retval;
	}
}
?>

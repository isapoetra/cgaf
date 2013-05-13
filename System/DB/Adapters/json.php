<?php
namespace System\DB\Adapters;

use System\DB\Adapters\JSON\JSONFieldInfo;
use System\DB\Adapters\JSON\JSONSQL;
use System\DB\Adapters\JSON\JSONTable;
use \CGAF;
use System\DB\DBConnection;
use System\DB\DBReflectionClass;
use System\DB\DBException;
use \System\DB\DBFieldDefs;

class JSON extends DBConnection {
	private $_path;
	private $_objects = array();
	private $_lastInsertId;
	private static $_ErrorCode = array(1001 => 'unable to create table %s ,Empty Field', 1002 => 'Invalid field Reference on table %s field %s', 2001 => 'Invalid Table Name %s', 3001 => 'Invalid Field Value %s %s',
	);
	private $_trErrorCode = array();

	function Open() {
		parent::Open();
		if ($this->isConnected()) {
			return true;
		}
		if ($this->getArg('autocreate', true)) {
			$path = $this->getArg('host') . DS;
			if (!is_dir($path)) {
				$path = CGAF::getInternalStorage('db' . DS . $path . DS, true, true);
				if (!$path) {
					throw new DBException('unable to open storage');
				}
				$this->setArg('host', $path);
			}
			$path .= $this->getArg("database");
			if (!is_dir($path)) {
				$path = \Utils::makeDir($path);
			}
			\Utils::makeDir(array($path . DS . 'table'));
		}
		$path = \Utils::toDirectory($this->getArg('host') . $this->getArg('database') . DS);
		if (!is_dir($path)) {
			throw new DBException('Host Not Found ' . (CGAF_DEBUG ? " [$path]" : ''));
		}
		$this->_path = $path;
	}

	function Query($sql) {
		return $this->Exec($sql);
	}
	function unquote($s) {
		return trim($s, '\' ');
	}
	function quote($s) {
		return '\'' . $s . '\'';
	}
	function getLastSQL() {
		return JSONSQL::getLast();
	}
	function exec($sql) {
		return JSONSQL::getResults($this, $sql);
	}

	/**
	 * @param        $objectId
	 * @param string $objectType
	 * @param bool   $throw
	 *
	 * @return null|JSONTable
	 * @throws \System\DB\DBException
	 */
	function getObjectInfo($objectId, $objectType = "table", $throw = true) {
		$objectType = strtolower($objectType);
		$objectId = strtolower($objectId);
		$path = $this->_path . $objectType . DS;
		if (!isset($this->_objects[$objectType])) {
			$lists = \Utils::getDirList($path);
			$r = array();
			foreach ($lists as $l) {
				$t = null;
				try {
					$t = new JSONTable($this, $l);
				} catch (\Exception $e) {

				}
				if ($t) {
					$r[$l] = $t;
				}
			}
			$this->_objects[$objectType] = $r;
		}

		if (!isset($this->_objects[$objectType][$objectId])) {
			$path .= $objectId . DS;
			if (is_dir($path) && is_file($path . 'defs.json')) {
				$this->_objects[$objectType][$objectId] = new JSONTable($this, $objectId);
			}
		}
		$retval = isset($this->_objects[$objectType][$objectId]) ? $this->_objects[$objectType][$objectId] : null;
		if (!$retval && $throw) {
			throw new DBException('Unknown Object ' . $objectId);
		}
		return $retval;
	}

	function parseFieldCreate($name, $type, $width, $args = null) {
	}

	function getLimitExpr($start, $end) {
	}

	function fetchObject() {
	}

	function fetchAssoc() {
	}

	function isObjectExist($objectName, $objectType) {
		$o = $this->getObjectInfo($objectName, $objectType, false);
		return $o ? true : false;
	}

	public function getLastInsertId() {
		return JSONSQL::getLastInsertId();
	}

	public function getAffectedRow() {
	}

	function getPathFor($objectName, $type) {
		$tname = $this->getArg('prefix') . strtolower($objectName);
		return $this->_path . strtolower($type) . DS . $tname . DS;
	}

	private function _toDBFieldType($f) {
		switch (strtolower($f)) {
		case 'string':
		case 'varchar':
			return 'varchar';
		case 'timestamp':
		case 'int':
		case 'text':
		case 'boolean':
		case 'smallint':
		case 'integer':
		case 'int':
			return strtolower($f);
		case 'bool':
			return 'boolean';
		default:
			$ex = explode(' ', $f);
			if (count($ex) > 1) {
				return $this->_toDBFieldType($ex[0]);
			}
			ppd($f);
		}
	}

	public function createDBObjectFromClass($classInstance, $objecttype, $objectName) {
		$r = new DBReflectionClass($classInstance);
		$fields = $r->getFields();
		switch (strtolower($objecttype)) {
		case 'table':
			$f = $this->getPathFor($objectName, $objecttype) . 'defs.json';
			if (is_file($f)) {
				return true;
			}
			$o = new \stdClass();
			$o->fields = array();
			$keys = $r->getPrimaryKey();
			if (is_string($keys)) {
				$keys = array($keys);
			}
			foreach ($fields as $field) {
				$o->fields[] = $this->getCreateField($field, $keys);
			}
			if (!count($o->fields)) {
				$this->_throwError(1001, $objectName);
			}
			$o->DataConfig = new \stdClass();
			$o->rows = array();
			\Utils::makeDir(dirname($f));
			file_put_contents($f, json_encode($o));
			clearstatcache(true, $f);
			return true;
			break;
		default:
			throw new \Exception("Error Processing Request", 1);
			break;
		}
	}

	private function getCreateField(DBFieldDefs $field, $keys) {
		$reference = array();
		if ($field->fieldreference) {
			$fr = explode(' ', $field->fieldreference);
			$rt = $this->getObjectInfo($fr[1], $fr[0]);
			$ff = $rt->getFieldInfo($fr[2]);
			if (!$ff) {
				$this->_throwError(1002, $fr[1], $fr[2]);
			}
			$ref = new \stdClass();
			$ref->type = $fr[0];
			$ref->object = $fr[1];
			$ref->expr = $fr[2];
			$reference[] = $ref;
		}
		$ftype = $field->fieldtype ? $this->_toDBFieldType($field->fieldtype) : 'varchar';
		$flength = isset($field->fieldlength) ? $field->fieldlength : 40;
		switch ($ftype) {
		case 'boolean':
			$flength = 1;
			break;
		}
		$fi = new JSONFieldInfo($this);
		$fi->field_name = $field->fieldname;
		$fi->field_width = $flength;
		$fi->field_type = $ftype;
		$fi->auto_inc = $field->isAutoIncrement();
		$fi->primary = in_array($fi->field_name, $keys);
		$fi->allow_null = $field->isAllowNull();
		$fi->default_value = $field->fielddefaultvalue;
		$fi->extra = $field->fieldextra;
		$fi->reference = $reference;
		return $fi;
	}

	function checkPrivs($o) {
	}

	function isAllow($action, $type, $id) {
		return true;
	}

	function on($mode, $type, $o) {
		switch ($mode) {
		case 'drop':
			if (isset($this->_objects[$type]) && isset($this->_objects[$type][$o])) {
				unset($this->_objects[$type][$o]);
			}
			break;
		}
	}

	function _throwError($code) {
		if (isset($this->_trErrorCode[$code])) {
			$code = $this->_trErrorCode[$code];
		} elseif (isset(self::$_ErrorCode[$code])) {
			$code = self::$_ErrorCode[$code];
		}
		$args = func_get_args();
		array_shift($args);
		$code = $args ? @vsprintf($code, $args) : $code;
		throw new DBException($code);
	}

	function getSQLCreateTable($o) {
		// TODO: Implement getSQLCreateTable() method.
	}
}
?>

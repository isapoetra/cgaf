<?php
namespace System\DB\Adapters;
use System\DB\DBResultList;
use System\DB\DBQuery;
use System\DB\Table;
use System\DB\DB;
use \String;
use \CGAF;
use System\DB\DBConnection;
use System\DB\DBReflectionClass;
use System\DB\DBException;
use \System\DB\DBFieldDefs;

class JSON extends DBConnection {
	private $_path;
	private $_objects = array();
	private static $_ErrorCode
	= array(
			1002 => 'Invalid field Reference on table %s field %s',
			2001 => 'Invalid Table Name %s',
			3001 => 'Invalid Field Value %s %s'
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
			\Utils::makeDir(
					array(
							$path . DS . 'table'
					)
			);
		}
		$path = \Utils::toDirectory($this->getArg('host') . $this->getArg('database') . DS);
		if (!is_dir($path)) {
			throw new DBException ('Host Not Found ' .(CGAF_DEBUG ? " [$path]" : ''));
		}
		$this->_path = $path;
	}

	function Query($sql) {
		return $this->Exec($sql);
	}

	function quote($s) {
		return '\'' . $s . '\'';
	}

	function Exec($sql) {
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
		if (!isset ($this->_objects [$objectType])) {
			$lists = \Utils::getDirList($path);
			$r = array();
			foreach ($lists as $l) {
				try {
					$t =  new JSONTable ($this, $l);
				}catch(\Exception $e) {

				}
				if ($t) {
					$r [$l] = $t;
				}
			}
			$this->_objects [$objectType] = $r;
			// ppd($this->_objects);
		}

		if (!isset ($this->_objects [$objectType] [$objectId])) {
			$path .= $objectId . DS;
			if (is_dir($path) && is_file($path.'defs.json')) {
				$this->_objects [$objectType] [$objectId] = new JSONTable ($this, $objectId);
			}
		}
		$retval = isset ($this->_objects [$objectType] [$objectId]) ? $this->_objects [$objectType] [$objectId] : null;
		if (!$retval && $throw) {
			throw new DBException ('Unknown Object ' . $objectId);
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
	}

	public function getAffectedRow() {
	}

	function getPathFor($objectName, $type) {
		$tname = $this->getArg('prefix') . strtolower($objectName);
		return $this->_path . strtolower($type) . DS . $tname . DS;
	}

	private function _toDBFieldType($f) {
		switch (strtolower($f)) {
			case 'string' :
			case 'varchar' :
				return 'varchar';
			case 'timestamp':
			case 'int' :
			case 'text' :
			case 'boolean' :
			case 'smallint':
				return strtolower($f);
			case 'bool' :
				return 'boolean';
			
			default :
				$ex = explode(' ', $f);
				if (count($ex) > 1) {
					return $this->_toDBFieldType($ex [0]);
				}
				ppd($f);
		}
	}

	public function createDBObjectFromClass($classInstance, $objecttype, $objectName) {
		$r = new DBReflectionClass ($classInstance);
		$fields = $r->getFields();
		switch (strtolower($objecttype)) {
			case 'table' :
				$f = $this->getPathFor($objectName, $objecttype) . 'defs.json';
				if (is_file($f)) {
					return true;
				}
				$o = new \stdClass ();
				$o->fields = array();
				$keys = $r->getPrimaryKey();
				if (is_string($keys)) {
					$keys = array(
							$keys
					);
				}
				foreach ($fields as $field) {
					$o->fields [] = $this->getCreateField($field, $keys);
				}
				if (!count($o->fields)) {
					$this->_throwError(1001);
				}
				$o->DataConfig = array();
				$o->rows = array();
				\Utils::makeDir(dirname($f));
				file_put_contents($f, json_encode($o));
				clearstatcache(true, $f);
				return true;
				break;
			default :
				throw new \Exception ("Error Processing Request", 1);
				break;
		}
	}

	private function getCreateField(DBFieldDefs $field, $keys) {
		$reference = array();
		if ($field->fieldreference) {
			$fr = explode(' ', $field->fieldreference);
			$rt = $this->getObjectInfo($fr [1], $fr [0]);
			$ff = $rt->getFieldInfo($fr [2]);
			if (!$ff) {
				$this->_throwError(1002, $fr [1], $fr [2]);
			}
			$ref = new \stdClass ();
			$ref->type = $fr [0];
			$ref->object = $fr [1];
			$ref->expr = $fr [2];
			$reference [] = $ref;
		}
		$ftype = $field->fieldtype ? $this->_toDBFieldType($field->fieldtype) : 'varchar';
		$flength = isset ($field->fieldlength) ? $field->fieldlength : 40;
		switch ($ftype) {
			case 'boolean' :
				$flength = 1;
				break;
		}
		$fi = new JSONFieldInfo ($this);
		$fi->field_name = $field->fieldname;
		$fi->field_width = $flength;
		$fi->field_type = $ftype;
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
			case 'drop' :
				if (isset ($this->_objects [$type]) && isset ($this->_objects [$type] [$o])) {
					unset ($this->_objects [$type] [$o]);
				}
				break;
		}
	}

	function _throwError($code) {
		if (isset ($this->_trErrorCode [$code])) {
			$code = $this->_trErrorCode [$code];
		} elseif (isset (self::$_ErrorCode [$code])) {
			$code = self::$_ErrorCode [$code];
		}
		$args = func_get_args();
		array_shift($args);
		$code = @vsprintf($code, $args);
		throw new DBException ($code);
	}

	function getSQLCreateTable($o) {
		// TODO: Implement getSQLCreateTable() method.
	}
}

class JSONTable extends DBQuery {
	private $_db;
	private $_table;
	private $_defs;
	private $_tableDefs;
	protected $_path;
	private $_pk;

	function __construct(JSON $db, $t) {
		parent::__construct($db);
		$this->_table = $t;
		$this->_db = $db;
		$this->_path = $this->_db->getPathFor($this->_table, 'table');
		$f = $this->_path . 'defs.json';
		if (is_readable($f)) {
			$this->_tableDefs = json_decode(file_get_contents($f));
		} else {
			throw new DBException ('table not found ' . $t . '@' . $f);
		}
	}

	function __destruct() {
		$f = $this->_path . 'defs.json';
		if (is_file($f)) {
			file_put_contents($this->_path . 'defs.json', json_encode($this->_tableDefs));
		}
	}

	private function getDataConfig($name, $def = null) {
		if (!isset ($this->_tableDefs->DataConfig [$this->_table])) {
			$this->_tableDefs->DataConfig [$this->_table] = new \stdClass ();
		}
		if (!isset ($this->_tableDefs->DataConfig [$this->_table]->$name)) {
			if ($def !== null) {
				$this->_tableDefs->DataConfig [$this->_table]->$name = $def;
			}
			return $def;
		}
		return $this->_tableDefs->DataConfig [$this->_table]->$name;
	}

	function getNextAutoIncrement() {
		return $this->getDataConfig('increment', 0) + 1;
	}

	function getConnection() {
		return $this->_db;
	}

	function getTableName($includeDBName = false,$quote =true) {
		return $this->_table;
	}

	function getFields() {
		return $this->_getFieldDefs();
	}

	function getPrimaryKey() {
		if (!$this->_pk) {
			$this->_pk = array();
			foreach ($this->Fields as $v) {
				if ($v->primary) {
					$this->_pk [] = $v->field_name;
				}
			}
		}
		return $this->_pk;
	}

	protected function _getFieldDefs() {
		if (!$this->_defs) {
			$this->_defs = array();

			foreach ($this->_tableDefs->fields as $v) {
				$o = new JSONFieldInfo ($this);
				foreach ($v as $k => $j) {
					$o->$k = $j;
				}
				$this->_defs [$v->field_name] = $o;
			}
		}
		return $this->_defs;
	}

	function getFieldInfo($field) {
		$fields = $this->_getFieldDefs();
		return isset ($fields [$field]) ? $fields [$field] : null;
	}

	protected function load() {
		// TODO Optimize and Split based on config
		$f = $this->_path . 'rows.json';
		$rows = array();
		if (is_file($f)) {
			$rows = json_decode(file_get_contents($f));
		}
		return $rows;
	}

	private function rrmdir($dir) {
		if (is_dir($dir)) {
			$objects = scandir($dir);
			foreach ($objects as $object) {
				if ($object != "." && $object != "..") {
					if (filetype($dir . "/" . $object) == "dir") {
						$this->rrmdir($dir . "/" . $object);
					}
					else
					{
						unlink($dir . "/" . $object);
					}
				}
			}
			reset($objects);
			rmdir($dir);
		}
	}

	function drop($object=null, $what = "table") {
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

	private function _isDuplicate($v) {
		$cpk = $this->PrimaryKey;
		$rows = $this->load();
		//$retval = array ();
		//$f = $this->Fields;
		foreach ($rows as $r) {
			$eq = false;
			foreach ($cpk as $p) {
				$eq = $r->$p === $v [$p];
			}
			if ($eq) {
				return true;
			}
		}
		return false;
	}

	private function getFileFor($row) {
		return $this->_path . 'rows.json';
	}

	private function _putRow($row) {
		$f = $this->getFileFor($row);
		$rows = array();
		if (is_file($f)) {
			$rows = json_decode(file_get_contents($f));
		}
		$rows [] = $row;
		file_put_contents($f, json_encode($rows));
		return $this;
	}

	function insert($def, $value = null, $func = false) {
		$this->clear();
		$row = new \stdClass ();
		$cf = $this->getFields();
		foreach ($def as $k => $v) {
			$def [$k] = trim($v, '\'\' ');
		}
		$valid = array();
		foreach ($cf as $f) {
			$v = isset ($def [$f->field_name]) ? $def [$f->field_name] : null;
			/** @noinspection PhpUndefinedMethodInspection */
			if ($f->isValid($v)) {
				$valid [$f->field_name] = $v;
			} else {
				$this->_db->_throwError(3001, $f->field_name, ' for table ' . $this->_table);
			}
		}
		if ($this->_isDuplicate($valid)) {
			throw new DBException ('Duplicate value');
		}
		$this->_putRow($valid);
		return true;
	}
}

class JSONTableSQL extends JSONTable {
	private $_filter = array();
	private $_select = array();
	private $_expr=array();
	function clear($what = 'all') {
		$this->_filter = array();
		return $this;
	}

	function filterResult($r) {
		$this->_filter [] = func_get_args();
	}
	function addSelectExpr($expr) {
		$alias = str_replace('`', '', isset($expr['alias']) ? $expr['alias'] :ppd($expr));
		$this->_expr[$alias] = $expr['sub_tree'];
		$this->addSelect($alias);
	}
	function addSelect($c) {
		if ($c === '*') {
			$fields = array_keys($this->_getFieldDefs());
			foreach ($fields as $f) {
				$this->addSelect($f);
			}
			return $this;
		}
		$this->_select [] = trim($c);
		return $this;
	}

	private function _iseqfilt($o) {
		$f = $this->_filter;
		$sfilt = '';
		$idx = 0;
		foreach ($f as $ff) {
			$oper = $ff [1];
			$val = trim($ff [2], '\' ');
			switch ($oper) {
				case '=' :
					$oper = '===';
					break;
			}
			$sfilt .= '(\'' . $o->$ff [0] . '\'' . $oper . '\'' . $val . '\')';
			if ($idx < count($f) - 1) {
				$sfilt .= '&&';
			}
			$idx++;
		}
		eval ("\$sfilt=$sfilt;");
		return $sfilt;
	}

	function load() {
		$rows = parent::load();
		if (count($rows) == 0) {
			return $rows;
		}
		if ($this->_filter) {
			$retval = array();
			foreach ($rows as $row) {
				if ($this->_iseqfilt($row)) {
					$retval [] = $row;
				}
			}
			$rows = $retval;
		}
		return $this->filtSelect($rows);
	}
	private function _parseExprRow($expr,$row) {
		$nexpr = '';
		foreach($expr as $ex) {
			switch ($ex['expr_type']) {
				case 'colref':
					$f = explode('.',$ex['base_expr']);
					if (isset($row->{$f[1]})) {
						$nexpr .= '\''.$row->{$f[1]}.'\'';
					}
					break;
				case 'operator':
					switch ($ex['base_expr']) {
						case '+':
							$nexpr .= '.';
							break;
						default:
							throw new DBException('unhandled operator expression '.$ex['base_expr']);
					}
					break;
				default:
					ppd($ex);
			}
		}
		$rval = '';
		eval('$rval='.$nexpr.';');
		return $rval;
	}
	private function filtSelect($row) {
		if (!$this->_select) {
			return $row;
		}
		$retval = array();
		foreach ($row as $r) {
			$n = new \stdClass ();
			foreach ($this->_select as $v) {
				if (isset($this->_expr[$v])) {
					$n->$v =$this->_parseExprRow($this->_expr[$v],$r);
				}else{
					$n->$v = $r->$v;
				}
			}
			$retval [] = $n;
		}
		return $retval;
	}
}

class JSONFieldInfo extends \System\DB\TableField {
	private function to($type, $val) {
		if ($val === null) {
			return null;
		}
		switch ($type) {
			case 'boolean' :
				if (!is_bool($val)) {
					$val = ( bool )$val;
				}
				$val = ( bool )$val ? true : false;
				break;
			case 'varchar':
				$val = \Convert::toString($val);
				break;
			case 'timestamp':
				switch (strtoupper($val)) {
					case 'CURRENT_TIMESTAMP':
						$val = \CDate::Current();
						break;
					default:
						ppd($val);
				}
				break;
			default :
				ppd($type);
				break;
		}
		return $val;
	}

	function isValid(&$val) {
		$val = $this->to($this->field_type, $val);
		if (trim($val) == '' && $this->extra && $table = $this->getTable()) {
			$ex = explode(' ', $this->extra);
			foreach ($ex as $e) {
				switch (strtolower($e)) {
					case 'auto_increment' :
						$val = $table->getNextAutoIncrement();
						break;
					default :
						ppd($e);
						break;
				}
			}
		}
		if ((!$this->allow_null || $this->primary) && trim($val) === '') {
			if ($this->default_value) {
				$val = $this->to($this->field_type, $this->default_value);
				return true;
			}
			return false;
		}
		if ($this->reference) {
			$q = new DBQuery ($this->_connection);
			//$valid = false;
			foreach ($this->reference as $ref) {
				switch ($ref->type) {
					case 'table' :
						$q->clear();
						$q->addTable($ref->object);
						$q->select($ref->expr);
						$q->where($q->quoteTable($this->field_name) . '=' . $q->quote($val));
						$o = $q->loadObject();
						if (!$o) {
							return false;
						}
						break;
					default :
						ppd($ref);
				}
			}
		}
		return true;
	}
}

class JSONSQL {
	/**
	 * @var JSON
	 */
	private $_db;
	private $_objects = array();

	function __construct(JSON $db) {
		$this->_db = $db;
	}

	/**
	 * @param $t
	 *
	 * @return JSONTableSQL
	 */
	private function _getTableObject($t) {
		$t = str_replace('`', '', $t);
		if (!$this->_db->isObjectExist($t, 'table')) {
			return null;
		}
		if (isset ($this->_objects [$t])) {
			/** @noinspection PhpUndefinedMethodInspection */
			return $this->_objects [$t]->clear();
		}
		$this->_objects [$t] = new JSONTableSQL ($this->_db, $t);
		return $this->_objects [$t];
	}

	private function _normalizew($tables, &$where) {
		if (count($tables) === 1) {
			$k = array_keys($tables);
			$tname = $tables [$k [0]]->TableName;
			if ($where) {
				foreach ($where as $kw => $w) {
					switch ($w ['expr_type']) {
						case 'expression' :
							$st = $w ['sub_tree'];
							foreach ($st as $kt => $tree) {
								switch ($tree ['expr_type']) {
									case 'colref' :
										$col = explode('.', $tree ['base_expr']);
										if (!isset ($col [1])) {
											$where [$kw] ['sub_tree'] [$kt] ['base_expr'] = $tname . '.' . $tree ['base_expr'];
										}
										break;
									default :
										break;
								}
							}
							break;
						default :
							break;
					}
				}
			}
		}
	}

	private function prepareSQL($sql) {
		$sql = str_ireplace('#__', $this->_db->getArg('prefix', ''), $sql);
		$sql = str_ireplace('`', '', $sql);
		return $sql;
	}
	private function _sortRow($rows,$field,$dir='asc') {
		$nsort =array();
		foreach($rows as $row) {
			if (!isset($row[$field])) {
				pp($row);
				ppd($field);
			}
			$nsort[$row[$field]] =$row;
		}
		//TODO Check for type and smarter sorting
		switch($dir) {
			case 'asc':
				asort($nsort);
				break;
			case 'desc':
				arsort($nsort);
		}
		$retval = array();
		foreach ($nsort as $v) {
			$row =array();
			foreach($v as $vv) {
				$row[] = $vv;
			}
			$retval[] = $row;
		}
		return $retval;
	}
	private function _filterOrder($rows,$order,$fieldselect) {
		$retval = array();
		$nsort =array();
		foreach($order as $v) {
			$expt =explode('.',$v['base_expr']);
			$expt[1] = trim(str_replace('asc', '', $expt[1]));
			$nsort[$expt[1]] =strtolower($v['direction']);
		}
		//reduce workaround
		$nrows =array();
		foreach($rows as $k=>$v) {
			$row = array();
			foreach($fieldselect as $idx=>$field) {
				$t = $this->_getTableObject($field [0]);
				$fi = $t->getFieldInfo($field[1]);
				$row[$fi->field_name] = $v[$idx];
			}
			$nrows[]  =$row;
			
		}
		foreach($nsort as $k=>$v) {
			$rows = $this->_sortRow($nrows,$k,$v);
		}
		return $rows;
	}
	private function _execSelect($p) {
		$from = $p ['FROM'];
		$tables = array();
		foreach ($from as $v) {
			$t = $this->_getTableObject($v ['table']);
			if ($t) {
				$tables [$v ['alias']] = $t;
			} else {
				throw new DBException ('Table ' . $v ['table'] . ' not exists');
			}
		}
		$where = isset($p ['WHERE']) ? $p ['WHERE'] : null;
		$this->_normalizew($tables, $where);
		if ($where) {
			foreach ($where as $w) {
				switch ($w ['expr_type']) {
					case 'operator' :
						break;
					case 'expression' :
						$st = $w ['sub_tree'];
							
						if (count($st)===3) {
							switch ($st [0] ['expr_type']) {
								case 'colref' :
									$col = explode('.', $st [0] ['base_expr']);
									if (!isset ($tables [$col [0]])) {
										ppd($w);
									}
									$tables [$col [0]]->filterResult($col [1], $st [1] ['base_expr'], $st [2] ['base_expr']);
									break;
								case 'operator' :
									dj($where);
									break;
								default :
									dj($st);
									break;
							}
						}
						break;
					default :
						dj($where);
						dj($w);
						break;
				}
			}
		}
		$select = $p ['SELECT'];
		$k = array_keys($tables);
		$tname = $tables [$k [0]]->TableName;
		$fieldselect = array();
		foreach ($select as $s) {
			switch ($s ['expr_type']) {
				case 'operator' :
					if ($s ['base_expr'] !== '*') {
						dj($select);
					}
				case 'expression' :
					$tables [$tname]->addSelectExpr($s);
					$s['alias'] =str_replace('`', '', $s['alias']);
					$fieldselect []=array($tname,$s['alias']);
		
					break;
				case 'colref' :
					$rt = explode('.', $s ['base_expr']);
					if (count($rt) > 1) {
						$fieldselect [] = array(
								$rt [0],
								$rt [1]
						);
						$tables [$rt [0]]->addSelect($rt [1]);
						$rt = $rt [1];
					} else {
						$fieldselect [] = array(
								$tname,
								$rt [0]
						);
						$tables [$tname]->addSelect($rt [0]);
					}
					break;
				default :
					ppd($s);
			}
		}
		$rows = array();
		/**
		 * @var JSONTableSQL $v
		 */
		foreach ($tables as $v) {
			$rows [$v->getTableName()] = $v->load();
		}
			
		$retval = new \stdClass ();
		$retval->cols = array();
		$retval->rows = array();
		$rowt = $rows [$tname];
		foreach ($fieldselect as $k => $v) {
			$retval->cols[] = $v[1];
		}
		foreach ($rowt as $row) {
			$r = array();
			foreach ($fieldselect as $k => $v) {
				$v [1] = trim($v [1]);
				if ($v [0] === $tname) {
					$r [] = $row->$v [1];
				} else {
					$r [] = null;
				}
			}
			$retval->rows [] = $r;
		}
		if (isset($p['ORDER'])) {
			$retval->rows =  $this->_filterOrder($retval->rows,$p['ORDER'],$fieldselect);
		}
		
		return $retval;
	}
	public function exec($sql) {
		$sql = $this->prepareSQL($sql);
		$s = new \System\DB\PHPSQLParser ($sql);
		$this->_db->checkPrivs($s);
		$p = $s->parsed;
		//$retval = new \System\DB\DBResultList ();
		if (isset ($p ['SELECT'])) {
			return $this->_execSelect($p);
		} elseif (isset ($p ['INSERT'])) {
			$o = $p ['INSERT'];
			$t = $this->_getTableObject($o ['table']);
			if (!$t) {
				$this->_db->_throwError(2001, $o ['table']);
			}
			$ids = array();
			$c = $o ['cols'];
			$cols = $t->Fields;
			$tfields = array();
			foreach ($cols as $v) {
				$tfields [] = $v->field_name;
			}
			if ($c === 'ALL') {
				$c = array();
				$idx = 0;
				foreach ($o ['values'] as $v) {
					if (!isset ($tfields [$idx])) {
						break;
					}
					$c [$tfields [$idx]] = $v;
					$idx++;
				}
			} else {
				dj($c);
			}
			return $t->insert($c);
		} elseif (isset ($p ['DROP'])) {
			array_shift($p ['DROP']);
			$q = $p ['DROP'];
			$type = strtolower(array_shift($q));
			array_shift($q);
			if (count($q) == 1) {
				$o = $this->_db->getObjectInfo($q [0], $type,false);
				if ($o) {
					if ($o->drop()) {
						$this->_db->on('drop', $type, $q [0]);
						return true;
					}
					return false;
				}else{
					return true;
				}
			}
			pp($q);
			ppd($type);
		} else {
			throw new DBException('unhandled sql command');
			ppd($p);
		}
	}

	private static function toResultObject($res) {
		$retval = new DBResultList();
		if (!is_object($res)) {
			return $res;
		}
		foreach ($res->rows as $row) {
			$srow = new \stdClass();
			foreach ($res->cols as $idx=> $col) {
				$srow->$col = $row[$idx];
			}
			$retval->assign($srow);
		}
		return $retval;
	}

	public static function getResults($db, $sql) {
		$i = new JSONSQL ($db);
		$res = $i->exec($sql);
		return self::toResultObject($res);
	}
}

?>

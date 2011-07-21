<?php
if (! defined ( "CGAF" ))
	die ( "Restricted Access" );
using ( "System.DB.*" );
abstract class DBConnection implements IDBConnection {
	protected $_connArgs;
	private $_connected = false;
	protected $_resource;
	protected $_lastError;
	protected $_lastErrorCode;
	protected $_thows = true;
	protected $_db = null;
	protected $_result;
	protected $_database;
	protected $_lastSQL;
	function __construct($connArgs) {
		$this->_connArgs = $connArgs;
	}

	function __get($name) {
		return $this->getArg ( $name, null );
	}
	function getDatabase() {
		return $this->_database ? $this->_database : $this->getArg('database');
	}
	/**
	 *
	 * @param Exception
	 * @return void
	 */
	protected function throwError(Exception $ex) {
		$this->_lastError = $ex->getMessage ();
		if (CGAF_DEBUG) {
			Logger::write($this->getLastSQL(),-1,false);
		}
		//Logger::Warning ( 'DB::' . $this->_lastError . $this->_lastSQL );
		if ($this->_thows) {
			throw $ex;
		}
	}
	function setThrowOnError($value) {
		$this->_thows = $value;
	}
	function getArgs() {
		return $this->_connArgs;
	}
	function getArg($name, $default = null) {
		return isset ( $this->_connArgs [$name] ) ? $this->_connArgs [$name] : $default;
	}
	function quoteTable($table) {
		if (is_array($table)) {
			$retval = array();
			foreach ($table as $k=>$v) {
				$retval[]= $this->quoteTable($v);
			}
			return implode(',', $retval);
		}
		return $table;
	}

	function isConnected() {
		return $this->_connected;
	}
	function Log($msg, $level = 'db') {
		if ($this->getArg("debug")) {
			Logger::write ( "DB:: $msg", $level );
		}
	}
	abstract function Query($sql);
	abstract function Exec($sql);
	abstract function getObjectInfo($objectId, $objectType = "table", $throw = true);
	abstract function parseFieldCreate($name, $type, $width, $args = null);
	abstract function getLimitExpr($start, $end);
	abstract function fetchObject();
	abstract function isObjectExist($objectName, $objectType);
	function SelectDB($db) {
		$this->_db = $db;
	}
	function setConnected($value) {
		$this->_connected = $value;
	}
	function quote($s) {
		return $s;
	}

	function Open() {
		if ($this->_connected) {
			return true;
		}
		return $this->_connected;
	}

	function execBatch($sql) {
		$t = $this->_thows;
		$this->_thows = true;
		$r = new DBResultList ();

		foreach ( $sql as $s ) {
			if (! empty ( $s )) {
				$r->Assign ( $this->exec ( $s ) );
			}
		}
		$this->_thows = $t;
		return $r;
	}
	public function getLastSQL() {
		return $this->_lastSQL;
	}
	protected function setLastSQL($sql) {
		$this->_lastSQL = $sql;
	}
	protected function first(&$r) {

	}
	protected function unQuoteField($field) {
		return $field;
	}
	public abstract function getLastInsertId();
	public abstract function getAffectedRow();
	public abstract function createDBObjectFromClass($classInstance,$objecttype,$objectName);
	protected function toResultList() {
		$r = null;

		if ($this->_result) {
			$r = new DBResultList ();
			$r->setLastInsertId($this->getLastInsertId());
			$r->setAffectedRow($this->getAffectedRow());
			$this->first ( $r );
			while ( $row = $this->fetchObject () ) {
				$s = new stdClass ();
				foreach ( $row as $k => $v ) {
					$f = $this->unQuoteField ( $k );
					$s->$f = $v;
				}
				$r->Assign ( $s );
			}
		}
		return $r;
	}
	protected function toTableName($tbl) {
		$sql = str_ireplace ( "[table_prefix]", $this->getArg ( "table_prefix" ), $tbl );
		if (String::BeginWith ( $tbl, $this->getArg ( "table_prefix" ) )) {
			return $tbl;
		}
		if (! String::BeginWith ( $tbl, '#__' )) {
			$tbl = '#__' . $tbl;
		}
		$tbl = str_ireplace ( "#__", $this->getArg ( "table_prefix" ), $tbl );
		return $tbl;
	}
	protected function prepareQuery($sql) {
		if (String::BeginWith($sql,'drop',false) || String::BeginWith($sql,'create',false)) {
			$this->_objects = array();
		}
		$sql = str_ireplace ( "[table_prefix]", $this->getArg ( "table_prefix" ), $sql );
		$sql = str_ireplace ( "#__", $this->getArg ( "table_prefix" ), $sql );
		$this->_lastSQL=$sql;
		return $sql;
	}
	public function DateToDB($date = null) {
		$dt = new CDate ( $date );
		return $dt->format ( FMT_DATETIME_MYSQL );

	}
	public function drop($id, $type = 'table') {
		if ($this->isObjectExist ( $id, $type )) {
			$this->Exec ( 'drop ' . $type . ' ' . $this->toTableName ( $id ) );
		}
	}
	public function getInstallFile($table) {

		$path = CGAF::getInternalStorage ( 'db//install/' . $this->getArg ( 'type' ) . '/' );
		if ($path && is_dir ( $path )) {
			$path .= $table . '.sql';
			return is_file ( $path ) ? Utils::ToDirectory ( $path ) : null;
		}
		return null;
	}
	public function getFieldConfig($fieldType=null) {
		return array();
	}
}
?>
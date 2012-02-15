<?php
namespace System\DB;
abstract class DBFieldInfo {
	public $field_name;
	public $field_type;
	public $field_width;
	public $allow_null;
	public $default_value;
	public $primary;
	public $extra;
	public $reference;
	protected $_connection;
	
	function __construct(IDBConnection  $connection) {
		$this->_connection = $connection;
	}
	protected abstract function quoteValueForField($field,&$value=null);
	public function toString($value) {
		if ($this->quoteValueForField($this->field_type,$value)) {
			return $this->_connection->quote($value);
		}
		return $value;
	}

}
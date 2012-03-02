<?php
namespace System\DB;

class TableField extends DBFieldInfo {
	private $_table;
  /**
   * @param $table IDBConnection|Table|DBQuery
   */
	function __construct( $table) {
		if ($table instanceof IDBConnection) {
			parent::__construct ($table);
		}else{
			$this->_table = $table;
			parent::__construct ( $table->getConnection () );
		}	
	}
	protected function getTable() {
		return $this->_table;
	}
	protected function quoteValueForField($field, &$value = null) {
		return $value;
	}
}

?>
<?php
namespace System\DB;
class DBResultList implements \Iterator {
	private $_crow = 0;
	private $_rows = array();
	private $_insert_id=null;
	private $_affectedRow=null;
	function Assign($o) {
		$this->_crow = - 1;
		$this->_rows [] = $o;

	}
	function getLastInsertId() {
		return $this->_insert_id;
	}
	function setAffectedRow($value) {
		return $this->_affectedRow;
	}
	function getAffectedRow() {
		return $this->_affectedRow;
	}
	function setLastInsertId($id) {
		$this->_insert_id =$id;
	}
	function count() {
		return count($this->_rows);
	}
	function current() {
		if ($this->_crow < $this->count()) {
			return $this->Rows($this->_crow);
		}
		return null;
	}

	function First() {
		if (count($this->_rows)) {
			$this->_crow = 0;
			return $this->_rows [0];
		}
		return null;
	}
	function Rows($row) {
		if ($row >= 0 && $row < count($this->_rows)) {
			return $this->_rows [$row];
		}
		return null;
	}
	function next() {
		$this->_crow ++;

		return $this->current();
	}
	/**
	 *
	 */
	public function key() {

	}

	/**
	 *
	 */
	public function valid() {
		return $this->_crow >= 0 && $this->_crow < $this->count();
	}

	/**
	 *
	 */
	public function rewind() {
		$this->_crow = - 1;
	}

}
?>
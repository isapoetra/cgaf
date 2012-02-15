<?php
namespace System\DB;
class DBResultList implements \Iterator {
	private $_crow = 0;
	private $_rows = array();
	private $_insert_id = null;
	private $_affectedRow = null;
	private $_errors=array();
	function Assign($o) {
		$this->_crow = -1;
		if ($o instanceof \Exception) {
			$this->_errors[] =  $o;
		}else{
			$this->_rows[] = $o;
		}
	}
	function getError() {
		return $this->_errors;
	}
	function hasError () {
		return count($this->_errors)>0;
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
		$this->_insert_id = $id;
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
			return $this->_rows[0];
		}
		return null;
	}
	function Rows($row) {
		if ($row >= 0 && $row < count($this->_rows)) {
			return $this->_rows[$row];
		}
		return null;
	}
	function next() {
		$this->_crow++;
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
		$this->_crow = -1;
	}
	private function rowToHash($row, $fk, $fv, &$retval) {
		$retval[$row->$fk] = $row->$fv;
		return $retval;
	}
	/**
	 *
	 * Enter description here ...
	 * @param string $fk Field Key
	 * @param string $fv Field Value
	 */
	public function toHashList($fk = null, $fv = null) {
		$r = $this->First();
		if (!$r) {
			return array();
		}
		$rk = array_keys(get_object_vars($r));
		$fk = $fk ? $fk : $rk[0];
		$fv = $fv ? $fv : (isset($rk[1]) ? $rk[1] : $rk[0]);
		$retval = array();
		$this->rowToHash($r, $fk, $fv, $retval);
		while ($r = $this->next()) {
			$this->rowToHash($r, $fk, $fv, $retval);
		}
		return $retval;
	}
}
?>
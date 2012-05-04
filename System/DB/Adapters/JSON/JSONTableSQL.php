<?php
namespace System\DB\Adapters\JSON;
use System\DB\DBException;

use System\DB\DBQuery;

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
	function exec($sql=null) {
		switch ($this->_type) {
			case DBQuery::MODE_UPDATE:
				if (!$this->_update) {
					throw new DBException('db.json.emptyupdate');
				}
				$row= $this->loadObject();
				
				foreach($this->_update as $k=>$v) {
					$f = $this->getField($k);
					if ($f->isValid($v[1])) {
						$row->{$k} =  $v[1];
					}
				}
				$this->_putRow($row);
				return true;
		}
	}
	function loadObject($o=null) {
		$retval = $this->load();
		return isset($retval[0]) ? $retval[0] :null;
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

?>
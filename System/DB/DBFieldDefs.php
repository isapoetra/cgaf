<?php 
class DBFieldDefs  {
	private $_vars = array();
	function __construct($arg=null) {
		
		if ($arg instanceof  SimplePHPDoc) {
			
			$arg = $arg->getVars();
			foreach ($arg as $k=>$v) {
				switch (strtolower($k)) {
					case 'fieldlength':
						$v = (int)$v;
						break;					
					default:
						;
					break;
				}
				$this->$k = $v;
			}
		}
	}
	function __set($name,$value) {
		$name= strtolower($name);
		$this->_vars[$name] = $value;
	}
	function __get($varname) {		
		$varname = strtolower($varname);
		switch ($varname) {
			case 'fieldtype':
				return isset($this->_vars['fieldtype']) ? $this->_vars['fieldtype'] : (isset($this->_vars['var']) ? $this->_vars['var'] : null);
			break;
		}
		return isset($this->_vars[$varname]) ? $this->_vars[$varname] : null;
	}
	function isAllowNull() {
		if ($this->isPrimaryKey() ) {
			return false;
		}
		if (!isset($this->_vars['fieldallownull'])) {
			return true;
		}		
		return ((string)$this->_vars['fieldallownull'] === 'true');
	}
	function  isPrimaryKey() {
		return (bool)$this->fieldisprimarykey ===true;		
	}
}
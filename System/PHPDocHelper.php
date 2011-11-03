<?php
class SimplePHPDoc {
	private $_doc;
	private $_vars;
	private $_caseSensitive = false;
	function __construct($s,$casesensitive=false) {
		$this->_caseSensitive =$casesensitive;
		$this->_doc = explode("\n", $s);
		foreach ( $this->_doc as $l ) {
			$l = trim($l);
			if (Strings::BeginWith($l,'/*') || Strings::EndWith($l,'*/') || $l=="*")
			{
				continue;
			}
			if (strpos($l,"@")!==false) {
				$k =substr($l,strpos($l,"@")+1);
				$var =  substr($k,0,strpos($k," "));
				$val = substr($k,strpos($k," ")+1);
				if (!$casesensitive) {
					$var = strtolower($var);
				}
				if ($var) {
					$this->_vars[$var] = $val;
				}
			}
		}
	}
	function getVar($varName,$def=null) {
		if (!$this->_caseSensitive) {
			$varName=strtolower($varName);
		}
		return isset($this->_vars[$varName]) ? $this->_vars[$varName] : $def;
	}
	function getVars() {
		return $this->_vars;

	}
}
class PHPDocHelper {
	/**
	 *
	 * Enter description here ...
	 * @param string $s
	 * @return SimplePHPDoc
	 */
	public static function parse($s) {
		$o = new SimplePHPDoc($s);

		return $o;
	}
}
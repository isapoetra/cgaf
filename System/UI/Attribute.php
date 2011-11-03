<?php
defined("CGAF") or die("Restricted Access");
/**
 *
 * Enter description here ...
 * @author e1
 * @deprecated
 *
 */
class Attribute implements IRenderable {
	protected $_ignore = array();
	private $_a = array();

	function __construct ($attr) {
		$this->parseAttribute($attr);
	}

	function get ($attrName) {
		return isset($this->_a[$attrName]) ? $this->_a[$attrName] : null;
	}

	function addIgnore ($o) {
		$this->_ignore[] = $o;
	}

	function parseAttribute ($attr) {
		if ($attr === null) {return false;}
		if (is_array($attr)) {
			$this->_a = $attr;
			return true;
		} else {
			throw new Exception("unknown Type " . gettype($attr));
		}
	}

	function Render ($return = false) {
		$r = null;
		foreach ($this->_a as $k => $v) {
			if (! in_array($k, $this->_ignore)) {
				$r .= "$k =\"$v\" ";
			}
		}
		return $r;
	}
}
?>
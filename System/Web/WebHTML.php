<?php
if (!defined("CGAF")) die("Restricted Access");

class TWebHTML implements IRenderable {
	private $_tag = "";
	private $_attr = array();
	private $_autoCloseTag = true;

	function __construct ($tag, $autoCloseTag = false, $attr = array()) {
		$this->_tag=$tag;
		$this->_attr = $attr;
		$this->_autoCloseTag = $autoCloseTag;
	}

	function RenderBeginTag () {
		Response::write($this->_tag, $this, $this->attr);
	}

	function Render () {
		$this->RenderBeginTag();
	}
}
?>
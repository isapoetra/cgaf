<?php
abstract class TBaseFeed implements IRenderable {
	protected $_data;
	private $_validHeader = array ();
	function Render($return = false) {

	}

	function __construct($data = null) {
		$this->_data = $data;
	}
	function setValidHeader($value) {
		$this->_validHeader = $value;
	}
	function setData($data) {
		$this->_data = $data;
	}
}
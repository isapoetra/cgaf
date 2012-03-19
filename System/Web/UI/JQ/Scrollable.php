<?php
namespace System\Web\UI\JQ;
class Scrollable extends Control {
	protected $_varName;
	protected $_jsObj = "scrollable";
	function __construct($id, $jsClientObj) {
		parent::__construct ( $id, $jsClientObj );
		$this->setTag ( "div" );
		$this->_varName = $id . "var";
	}
}
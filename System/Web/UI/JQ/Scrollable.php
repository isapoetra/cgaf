<?php
namespace System\Web\UI\JQ;
class Scrollable extends Control {
	protected $_varName;
	protected $_jsObj = "scrollable";
	function __construct($id, $template) {
		parent::__construct ( $id, $template );
		$this->setTag ( "div" );
		$this->_varName = $id . "var";
	}
}
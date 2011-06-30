<?php
defined("CGAF") or die("Restricted Access");

class TJQCombo extends JQControl {
	private $_data=array();
	protected $_keyField;
	protected $_valueField;
	function __construct($id, ITemplate $template,$data,$keyField,$valueField) {
		parent::__construct($id,$template);
		$this->_data =$data;
		$this->setTag("select");
		$this->setAttr("type","combo");
		$this->setAutoCloseTag(false);
		$this->_keyField = $keyField;
		$this->_valueField = $valueField;
	}
	function renderItems() {
		$retval = "";
		foreach ($this->_data as $v) {
			$retval .= "<option value=\"{$v->{$this->_keyField}}\">{$v->{$this->_valueField}}</option>";
		}
		return $retval;
	}
}
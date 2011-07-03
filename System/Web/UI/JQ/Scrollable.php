<?php

class TJQScrollable extends JQControl {
	protected $_varName;
	protected $_jsObj="scrollable";
	function __construct($id, $template) {
		parent::__construct($id, $template);
		$this->setTag("div");
		$this->_varName = $id . "var";
	}
}
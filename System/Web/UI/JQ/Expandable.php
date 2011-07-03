<?php
class JQExpandable extends JQControl {
	function  __construct($id) {
		parent::__construct($id,'expandable');
		$this->setTag('div');		
	}
	function getScript() {
		CGAFJS::loadPlugin('jq.plugins.expandable');
		return parent::getScript();
	}
}
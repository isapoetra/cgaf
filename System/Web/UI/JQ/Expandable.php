<?php
namespace System\Web\UI\JQ;
use System\Web\JS\CGAFJS;

class Expandable extends Control {
	function __construct($id) {
		parent::__construct ( $id, 'expandable' );
		$this->setTag ( 'div' );
	}
	function getScript() {
		CGAFJS::loadPlugin ( 'jq.plugins.expandable' );
		return parent::getScript ();
	}
}
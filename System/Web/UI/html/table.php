<?php
class HTMLTable extends WebControl {
	private $_url;
	private $_model;
	function __construct($attr = null) {
		parent::__construct ( 'table', false, $attr );
	}

	function newRow() {
		parent::addChild ( new WebControl ( 'tr', false ) );
	}

	private function lastChild() {
		$c = $this->getChilds ();
		if (count ( $c ) == 0) {
			$this->newRow();
			$c = $this->getChilds ();
		}
		return $c [count ( $c ) - 1];
	}

	function addChild($c,$attr=array()) {
		$li = new WebControl ( 'td', false,$attr);
		$li->addChild ( $c );
		return $this->lastChild ()->addChild ( $li );
	}

}
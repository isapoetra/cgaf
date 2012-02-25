<?php
namespace System\Web\UI\JQ;
abstract class InputControl extends Control {
	function __construct($inputtype, $attr) {
		parent::__construct ( null, null );
		$this->setTag ( 'input' );
		$this->setAttr ( 'type', $inputtype );
		$this->setAttr ( $attr );
	}
}
<?php
namespace System\Web\UI\Ext;
class Panel extends Control {
	function __construct($configs) {
		parent::__construct ( "G.Panel" );
		$this->setConfig ( $configs );
	}
}
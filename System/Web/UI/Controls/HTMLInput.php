<?php
namespace System\Web\UI\Controls;
class HTMLInput extends WebControl {
	function __construct($type) {
		parent::__construct('input');
		$this->setAttr('type',$type);
	}
}
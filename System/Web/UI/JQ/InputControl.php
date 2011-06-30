<?php
abstract class TJQInputControl extends JQControl {
	function __construct($inputtype,$attr) {
		parent::__construct(null,null);
		$this->setTag('input');
		$this->setAttr('type',$inputtype);
		$this->setAttr($attr);
	}
}
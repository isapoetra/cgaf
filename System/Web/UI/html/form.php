<?php
class HTMLForm extends WebControl {

	function __construct($action=null,$id=null,$method="post") {
		parent::__construct('form',false);
		$this->setAttr("action",$action);
		$this->setAttr("method",$method);
		$this->setId($id);
	}
}
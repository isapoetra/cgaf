<?php
namespace System\Web\UI\Controls;
use System\Session\Session;

use System\Web\Utils\HTMLUtils;

class Form extends WebControl {
	function __construct($action = null, $id = null, $method = "post") {
		parent::__construct('form', false);
		$this->setAttr("action", $action);
		$this->setAttr("method", $method);
		$this->setId($id);
	}
	function prepareRender() {
		parent::prepareRender();
		$this->addChild(HTMLUtils::renderHiddenField('__token', Session::get('__token')));
	}
	function setRenderToken($value) {
		$this->_renderToken=$value;
	}
}

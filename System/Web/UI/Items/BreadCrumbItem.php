<?php
namespace System\Web\UI\Items;
use System\Web\UI\Controls\WebControl;
class BreadCrumbItem extends WebControl {
	private $_link;
	function __construct() {
		parent::__construct('li');
		$this->_link = new WebControl('a');
	}
	function prepareRender() {
		if ($this->_link->getAttr('href')) {
			$this->addChild($this->_link);
		} else {
			$this->setText($this->_link->getText());
		}
	}
	function bind($o) {
		if (is_array($o)) {
			if (isset($o['class'])) {
				$this->setAttr('class', $o['class']);
			}
			if (isset($o['url'])) {
				$this->_link->setAttr('href', $o['url']);
			}
			if (isset($o['title'])) {
				$this->_link->setText($o['title']);
			}
		}
	}
}

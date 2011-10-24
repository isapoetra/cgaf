<?php
namespace System\Web\UI\Controls;
use System\Web\UI\Items\BreadCrumbItem;
class BreadCrumb extends WebControl {
	function __construct() {
		parent::__construct('ul');
		$this->setAttr('class', 'breadcrumbs');
	}
	function addItem($item) {
		if (is_array($item)) {
			$nitem = new BreadCrumbItem();
			$nitem->bind($item);
			$item = $nitem;
		}
		$this->addChild($item);
	}
	function addItems($arrItems) {
		foreach ($arrItems as $item) {
			$this->addItem($item);
		}
	}
	function Render($return = false) {
		if (!$this->_childs) {
			return '';
		}
		return parent::Render($return);
	}
}

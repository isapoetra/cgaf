<?php
namespace System\Web\UI\Controls;
use System\Web\UI\Items\BreadCrumbItem;
class BreadCrumb extends WebControl {
	function __construct() {
		parent::__construct ( 'ul' );
		$this->setAttr ( 'class', 'breadcrumb' );
	}
	function addItem($item) {
		if (is_array ( $item )) {
			$nitem = new BreadCrumbItem ();
			$nitem->bind ( $item );
			$item = $nitem;
		}
		$this->addChild ( $item );
	}
	function renderItems() {
		$retval = "";
		$idx = 0;
		$cnt = count ( $this->_childs ) - 1;
		foreach ( $this->_childs as $item ) {
			$retval .= '<li';
			if ($idx >= $cnt && $item instanceof \Control) {
				$retval .= ' class="active"';
			}
			$retval .= '>';
			$retval .= \Utils::toString ( $item );
			if ($idx < $cnt) {
				$retval .= '<span class="divider">/</span>';
			}
			$retval .= '</li>';
			$idx ++;
		}
		return $retval;
	}
	function addItems($arrItems) {
		foreach ( $arrItems as $item ) {
			$this->addItem ( $item );
		}
	}
	function Render($return = false) {
		if (! $this->_childs) {
			return '';
		}
		return parent::Render ( $return );
	}
}

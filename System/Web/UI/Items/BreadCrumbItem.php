<?php
namespace System\Web\UI\Items;
use System\Web\UI\Controls\WebControl;
class BreadCrumbItem extends WebControl {
	private $_link;
	function __construct() {
		parent::__construct ( 'a' );	
	}
	
	function bind($o) {
		if (is_array ( $o )) {
			if (isset ( $o ['class'] )) {
				$this->setAttr ( 'class', $o ['class'] );
			}
			if (isset ( $o ['url'] )) {
				$this->setAttr ( 'href', $o ['url'] );
			}
			if (isset ( $o ['title'] )) {
				$this->setText ( $o ['title'] );
			}
		}
	}
}

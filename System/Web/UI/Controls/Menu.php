<?php
namespace System\Web\UI\Controls;
class Menu extends WebControl {
	private $_replacer = array ();
	function __construct() {
		parent::__construct ( 'ul', false, array (
				'class' => 'nav' 
		) );
	}
	function setReplacer($r) {
		if ($r) {
			if (is_array ( $r ) || is_object ( $r )) {
				foreach ( $r as $k => $v ) {
					$this->_replacer [$k] = $v;
				}
			} else {
				$this->_replacer = $r;
			}
		}
	}
	function getReplacer() {
		return $this->_replacer;
	}
}
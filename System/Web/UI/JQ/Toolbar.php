<?php

class JQToolbar extends JQControl {
	function __construct($id = null) {
		parent::__construct ( $id );
		$this->setAttr ( array (
				'class' => 'ui-widget-header ui-corner-all toolbar'
		) );
	}
	function addSeparator() {
		$this->addChild ( new WebControl ( 'div', false, array (
				'class' => 'separator'
		) ) );
	}
	function addButton($config) {
		$id = isset($config['id']) ? $config['id'] : $this->getId().'-button-'. (count($this->_childs) +1);
		$btn = new JQButton($id);
		$btn->setConfig($config);
		$this->addChild($btn);
		return $btn;
	}
	function prepareRender() {
		return parent::prepareRender ();
	}
	function RenderScript($return = false) {
		return parent::RenderScript ( $return );
	}
}

?>
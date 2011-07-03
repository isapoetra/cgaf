<?php
abstract class Control extends Object implements IRenderable {
	private $_properties;
	protected $_childs = array ();
	private $_container;
	function __construct() {
		$this->_properties = array ();
		$this->_events = array ();
	}
	function getAppOwner() {
		return AppManager::getInstance();
	}
	function setProperty($propertyName, $value = null) {
		if (is_array ( $propertyName )) {
			foreach ( $propertyName as $k => $v ) {
				$this->_properties [$k] = $v;
			}
		} elseif ($propertyName) {
			$this->_properties [$propertyName] = $value;
		}
		return $this;
	}
	protected function setChilds($items) {
		$this->_childs = $items;
	}
	function &getChilds() {
		return $this->_childs;
	}
	protected function getProperties() {
		return $this->_properties;
	}
	function getProperty($name) {
		return isset ( $this->_properties [$name] ) ? $this->_properties [$name] : null;
	}
	function addChild($c) {
		if ($c === null) {
			return $this;
		}
		$this->_childs [] = $c;
		return $this;
	}

	protected function renderItems() {
		$retval = "";
		foreach ( $this->_childs as $item ) {
			if (is_object ( $item ) && $item instanceof IRenderable) {
				$retval .= $item->render ( true );
			} elseif (is_string ( $item )) {
				$retval .= $item;
			} elseif (CGAF_DEBUG) {
				$retval .= pp ( $item, true );
			}
		}
		return $retval;
	}

	public function renderChilds() {
		//$this->prepareRender ();
		return $this->renderItems ();
	}
	protected function prepareRender() {
		if ($this->_container) {
			$this->_container->prepareRender ();
		}
		foreach ( $this->_childs as $item ) {
			if (is_object ( $item ) && $item instanceof IRenderable) {
				$item->prepareRender ();
			}
		}
		//$this->preRender();
	}
	function Render($return = false) {
		$this->prepareRender ();
		return $this->renderItems ( $return );
	}
}
?>

<?php

abstract class MVCView implements IRenderable {
	private $_controller;
	
	protected $_state;
	/**
	 * 
	 * Enter description here ...
	 * @param MVCController $controller
	 */
	function __construct(MVCController $controller = null) {
		$this->_controller = $controller;
	}
	public abstract function display() ;
	function getState($stateName, $default = null) {
		if (! $this->_state) {
			throw new SystemException ( "Invalid State" );
		}
		return $this->_state->getState ( $stateName, $default );
	}
	
	function setState($stateName, $value) {
		if (! $this->_state) {
			throw new SystemException ( "Invalid State" );
		}
		return $this->_state->setState ( $stateName, $value );
	}
	
	/* (non-PHPdoc)
	 * @see IRenderable::Render()
	 */
	public function Render($return = false) {
		return $this->display ();
	}

}

?>
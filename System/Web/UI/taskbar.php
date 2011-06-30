<?php
class Taskbar extends WebControl {
	private $_desktop;
	private $_startMenu;
	private $_tasklist;
	private $_traylist;
	function __construct(IDesktop $desktop) {
		parent::__construct ( "div", false, array ('class' => 'desktop-taskbar' ) );
		$this->_desktop = $desktop;
		$this->_tasklist = new WebControl('div',false,array('class'=>'task-container'));
		$this->_traylist = new WebControl('div',false,array('class'=>'task-container'));
	}
	function getMainMenu() {
		if (! $this->_startMenu) {
			$this->_startMenu = new StartMenu ( 'start-menu' );
			$this->_startMenu->loadFromFile ( $this->_desktop->getInternalStoragePath () . DS . 'ui/menu.xml' );
		}
		return $this->_startMenu;
	}
	function prepareRender() {
		
		$this->addChild($this->getMainMenu());
		$this->addChild($this->_tasklist);
		$this->addChild($this->_traylist);
	}
}
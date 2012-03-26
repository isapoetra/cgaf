<?php
namespace System\Web\UI\Controls;
use System\Web\UI\Controls\WebControl;
class TabItem  {
	private $_title;
	private $_content;
	private $_active=false;
	function __construct($title,$content,$id=null) {
		$this->_id = $id  ? $id :\Utils::generateId('tab');
		$this->_title=$title;
		$this->_content= $content;
	}
	function setId($id) {
		$this->_id=$id;
	}
	function setActive($value) {
		$this->_active=$value;
	}
	function renderTitle() {
		return '<li class="'.($this->_active ? ' active':'').'"><a href="#'.$this->_id.'" data-toggle="tab">'.$this->_title.'</a></li>';
	}
	function renderContent() {
		return '<div class="tab-pane'.($this->_active ? ' active':'').'" id="'.$this->_id.'"><p>'.$this->_content.'</p></div>';
	}
}
class Tab extends WebControl{
	private $_tabs;
	private $_tabPosition='top';
	private $_ul;
	private $_content;
	private $_activeTab=null;
	function __construct($id=null) {
		parent::__construct('div');
		$this->_ul = new WebControl('ul');
		$this->_ul->setClass('nav nav-tabs');
		$this->_content = new WebControl('div');
		$this->_content->setClass('tab-content');
		$this->setClass('tabbable');
		$this->setId($id);
	}
	function setActiveTab($idx) {
		$this->_activeTab=$idx;
	}
	 
	function addTab($tab) {
		if (is_array($tab)) {
			$tmp = $tab;
			$tab = new TabItem($tmp['title'],$tmp['content']);
		}
		$this->_tabs[] =$tab;
	}
	function prepareRender() {
		if ($this->_activeTab ===null) {
			$this->_activeTab=0;
		}
		if (!isset($this->_tabs[$this->_activeTab])) {
			$this->_activeTab=0;
		}
		foreach($this->_tabs as $v) {
			$v->setActive(false);
		}
		if (isset($this->_tabs[$this->_activeTab])) {
			$this->_tabs[$this->_activeTab]->setActive(true);
		}
		foreach($this->_tabs as $id=>$v) {
			$v->SetId($id);
			$this->_ul->addChild($v->renderTitle());
		}
		$this->addChild($this->_ul);
		foreach($this->_tabs as $v) {
			$this->_content->addChild($v->renderContent());
		}

		$this->addChild($this->_content);
		switch ($this->_tabPosition) {
			case 'top':
				break;
			case 'left':
				$this->addClass('tabs-left');
				break;
			case 'right':
				$this->addClass('tabs-right');
				break;
			case 'below':
			case 'bottom':
				$this->addClass('tabs-below');
		}
	}

}
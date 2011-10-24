<?php
namespace System\Web\UI\Controls;
use \AppManager;
class Button extends WebControl {
	private $_icon = 'cleardot.gif';
	private $_url = null;
	private $_showlabel = true;
	private $_descr;
	function __construct($id = null, $attr = null) {
		parent::__construct('button');
		$this->setId($id);
		$this->setAttr($attr);
	}
	function setDescr($value) {
		$this->_descr = $value;
	}
	function setURL($value) {
		$this->_url = $value;
	}
	function setIcon($icon) {
		if ($icon) {
			$this->_icon = $icon;
		}
		return $this;
	}
	function setShowLabel($value) {
		$this->_showlabel = $value;
	}
	function getDescr() {
		return $this->_descr ? $this->_descr : $this->getTitle();
	}
	function prepareRender() {
		if ($this->_icon) {
			$icon = AppManager::getInstance()->getLiveAsset($this->_icon, 'icons');
			if (!$icon) {
				$icon = AppManager::getInstance()->getLiveData('cleardot.gif');
				$this->_showlabel = true;
			}
			if ($icon) {
				$this->add('<img src="' . $icon . '"/>');
			}
		}
		if ($this->_url) {
			if ($this->getAttr('rel') === '#overlay') {
				$this->setAttr('onclick', '$.openOverlay({url:\'' . $this->_url . '\',modal : true});return false;');
			} else {
				$this->setAttr('url', $this->_url);
			}
		}
		if ($this->getText()) {
			if ($this->_showlabel) {
				$this->add('<span>' . $this->getText() . '</span>');
			}
			$this->setText(null);
		}
		if ($this->getTitle()) {
			$this->setAttr('title', $this->getTitle());
			$this->setTitle(null);
		}
		/*$icon = $icon ?  $icon :'/Data/images/cleardot.gif';
		$attr = $descr ? $attr. ' title = "'.$descr.'"' : $attr;
		return '<button '.$attr.' url="'.$link.'"><img src="'.$icon.'"/><span>'.$title.'</span></button>';*/
	}
}

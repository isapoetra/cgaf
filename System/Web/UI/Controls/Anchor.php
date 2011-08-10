<?php
namespace System\Web\UI\Controls;
class Anchor extends WebControl implements \IRenderable {
	private $_link;
	private $_target;
	private $_title;
	private $_jsMode = true;
	private $_tooltip;
	private $_additionaljs;
	private $_class;
	function __construct($link, $title, $target=null,$tooltip=null, $jsMode = true,$additionaljs=null) {

		$this->_link = $link;
		$this->_target = $target;
		$this->_title = $title;
		$this->_jsMode = $jsMode;
		$this->_tooltip = $tooltip;
		$this->_additionaljs=$additionaljs;
		$attr =array();
		if ($tooltip) {
			$attr['title'] = $tooltip;
		}
		if ($target) {
			$attr['rel'] = $target;
		}

		$attr['href'] = $link;
		$this->setText($title);
		parent::__construct('a',true,$attr);
	}

	function setClass($class) {
		$this->_class =$class;
	}
	

	public static function link($link, $title, $target,$tooltip, $jsMode = true,$additionaljs=null) {		
		$link = new WebLink($link, $title, $target,$tooltip, $jsMode,$additionaljs);
		return $link->render(true);
	}
}
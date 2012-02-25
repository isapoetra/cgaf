<?php
namespace System\Web\UI\Controls;
class Anchor extends WebControl implements \IRenderable {
	private $_link;
	private $_target;
	private $_jsMode = true;
	
	private $_additionaljs;
	function __construct($link, $title, $target = null, $tooltip = null, $jsMode = true, $additionaljs = null) {
		parent::__construct ( 'a', true );
		$this->Action = $link;
		$this->Target = $target;
		if ($title) {
			$this->Text = $title;
		}
		$this->_jsMode = $jsMode;
		$this->Tooltip = $tooltip;
		$this->_additionaljs = $additionaljs;
	}
	function setTitle($value) {
		return $this->setAttr ( 'title', $value );
	}
	function getTitle() {
		return $this->getAttr ( 'title' );
	}
	
	function setToolTip($value) {
		if ($value) {
			$this->setAttr ( 'rel', 'tooltip' );
			$this->setAttr ( 'title', $value );
		}
	}
	function setTarget($t) {
		$this->setAttr ( 'target', $t );
	}
	function getTooltip() {
		return $this->getAttr ( 'title' );
	}
	function setAction($a) {
		return $this->setAttr ( 'href', $a );
	}
	function getAction() {
		return $this->getAttr ( 'href' );
	}
	function prepareRender() {
		if ($this->_additionaljs) {
			ppd ( $this );
		}
		$this->setText ( '<span>' . $this->Text . '</span>' );
		return parent::prepareRender ();
	}
	
	public static function link($link, $title, $target, $tooltip, $jsMode = true, $additionaljs = null) {
		$link = new Anchor ( $link, $title, $target, $tooltip, $jsMode, $additionaljs );
		return $link->render ( true );
	}
}
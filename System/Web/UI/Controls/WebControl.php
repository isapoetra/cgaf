<?php
namespace System\Web\UI\Controls;
use System\Web\Utils\HTMLUtils;
use System\Web\WebUtils;
use \Utils;
use \Request;
use \Convert;
class WebControl extends \Control implements \IRenderable {
	protected $_tag = "";
	protected $_attr = array();
	private $_autoCloseTag = true;
	protected $_title;
	private $_text;
	protected $_renderPrepared = false;
	function __construct($tag, $autoCloseTag = false, $attr = array()) {
		parent::__construct();
		$attr = $attr ? $attr : array();
		$attr = HTMLUtils::attributeToArray($attr);
		$this->_tag = $tag;
		if (!isset($attr['id'])) {
			$attr['id'] = Utils::generateId('c');
		}
		$this->setAttr($attr, null);
		$this->_autoCloseTag = $autoCloseTag;
	}
	function addStyle($name, $value = null) {
		$old = parent::getProperty('style',array());

		$n = HTMLUtils::mergeStyle($old, is_array($name) ? $name : array(
				$name => $value));
		return parent::setProperty('style',$n);
	}
	function add($c) {
		return parent::addChild($c);
	}
	function getText() {
		return $this->_text;
	}
	function setText($text) {
		$this->_text = $text;
	}
	function setClass($c) {
		parent::setProperties('class',$c);
	}

	function addClass($c) {
		if (!is_array($c)) {
			$c = explode(' ',$c);
		}
		$current  = explode(' ',$this->getProperty('class',''));
		foreach($c as  $a) {
			if (!in_array($c,$current)) {
				$current[]= $a;
			}
		}
		$this->setProperties('class', implode(' ',$current));
	}
	function removeClass($c) {
		if (is_string($c)) {
			$c = explode(' ',$c);
		}
		$current  = explode(' ',$this->getProperty('class',''));
		$rc = array();
		foreach($current as $k=>$v) {
			if (empty($v)) continue;
			if (!in_array($v,$c)) {
				$rc[]= $v;
			}
		}
		$this->setProperties('class', implode(' ',$rc));
		return $this;
	}
	function setProperty($propertyName,$value=null) {
		if (is_array($propertyName)) {
			foreach ( $propertyName as $k => $v ) {
				$this->setProperty($k,$v);
			}
			return $this;
		}
		switch (strtolower($propertyName)) {
			case 'style':
				return $this->addStyle($value);
			case 'class':
				$value = explode(' ',$value);
				$current  = $this->getProperty('class','');
				$current =  explode(' ',$current);
				foreach($value as $v) {
					if (!in_array($v,$current)) {
						$current[]= $v;
					}
				}
				$current =  trim(implode(' ',$current));
				return $this->setProperties('class',$current);
		}
		return parent::setProperty($propertyName,$value);
	}
	function getId() {
		if (!$this->getProperty("id")) {
			$this->setProperty("id", Utils::generateId('c'));
		}
		return $this->getProperty("id");
	}
	function setId($value) {		
		$this->setProperty("id", $value);
	}
	function setAutoCloseTag($value) {
		$this->_autoCloseTag = $value;
	}
	function setTitle($value) {
		$this->_title = $value;
	}
	function getTitle() {
		return $this->_title ? $this->_title : $this->getText();
	}
	protected function getAttr($name) {
		return $this->getProperty($name);
	}
	protected function hasAttr($name) {
		return isset($this->_attr[$name]);
	}
	function setConfig($attName, $Value = null) {
		return $this->setProperty($attName, $Value);
	}
	/**
	 *
	 * @param array $values
	 * @deprecated
	 */
	function setAttrs( $values){

		return $this->setAttr($values);
	}
	function setAttr($attName, $Value = null) {
		if (!is_array($attName) && empty($Value)) {
			return $this->removeProperty($attName);
		}
		return $this->setProperty($attName, $Value);
	}
	function setTag($tag) {
		$this->_tag = $tag;
		return $this;
	}
	function renderAttributes($attr = null) {
		$attr = $attr ? $attr : $this->getProperties();
		$retval = "";
		foreach ($attr as $k => $v) {
			if (is_string($v)) {
				$v = trim($v);
			} else {
				$v = Utils::arrayImplode($v, ':', ';');
			}
			if ($v !== null) {
				if (strtolower(substr($k, 0, 2)) === 'on' && strpos( $v,'$') === false) {
					$v = \System\Web\JS\JSUtils::addSlash($v);
				}
				$retval .= ' ' . $k . '="' . $v . '"';
			}
		}
		return $retval;
	}
	protected function RenderBeginTag() {
		$retval = "<$this->_tag" . $this->renderAttributes() . ($this->_autoCloseTag && (count($this->getChilds()) == 0) && !$this->getText() ? "/>" : ">");
		return $retval;
	}
	protected function renderEndTag() {
		return (!$this->_autoCloseTag || count($this->getChilds()) > 0 || $this->getText() ? "</{$this->_tag}>" : "");
	}
	protected function renderItems() {
		$retval = "";
		foreach ($this->getChilds() as $item) {
			$retval .= Convert::toString($item);
		}
		return $retval;
	}
	protected function renderBeginLabel() {
		return ($this->_title ? "<label>" . $this->_title : "");
	}
	protected function renderEndLabel() {
		return ($this->_title ? "</label>" : "");
	}

	protected function prepareRender() {
		$this->_renderPrepared = true;
		//$this->prepareRender ();
	}
	function Render($return = false) {
		if (!$this->_renderPrepared) {
			$this->prepareRender();
		}
		if (Request::isDataRequest()) {
			return $this->renderItems();
		}
		$retval = $this->renderBeginLabel() . $this->RenderBeginTag() . $this->_text . $this->renderItems() . $this->renderEndTag() . $this->renderEndLabel();
		if (!$return) {
			\Response::write($retval);
		}
		return $retval;
	}
}

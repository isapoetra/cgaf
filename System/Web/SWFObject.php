<?php
namespace System\Web;
use System\Web\UI\Controls\WebControl;
class SWFObject extends WebControl implements \IRenderable {
	private $_params=array();
	function __construct($source) {
		parent::__construct('object',true);
		$this->setAttr('type',"application/x-shockwave-flash");
		$this->setMovie($source);
		$this->setParam('allowScriptAccess','always');

	}
	protected function preRender() {
		foreach($this->_params as $k=>$v) {
			$o =  new WebControl('param',true);
			$o->setAttr('name',$k);
			$o->setAttr('value',$v);
			$this->add($o);
		}
	}
	function setMovie($value) {
		$this->setAttr('data',$value);

	}
	function setParam($name,$value) {
		$this->_params[$name] = $value;
	}


}
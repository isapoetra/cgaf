<?php
class TJQWizard extends TJQScrollable {
	private $_steps;
	function __construct($id, $template, $steps) {
		parent::__construct ( $id, $template );
		//$this->setAttr("class","wizard");
		$this->setConfig ( "size", 1 );
		$this->setConfig ( "clickable", false );
		$this->_jsObj = "tabs";
		$this->_steps = $steps;
		$this->setAttr ( 'class', 'ui-wizzard' );
	}
	function getClientId() {
		return $this->getId () . '-wizzard-content';
	}
	function prepareRender() {
		if ($this->_prepared) {
			return;
		}
		$this->_prepared = true;
		$ul = new WebControl ( "ul" );
		$ul->setId ( $this->getId () . "-wizard-navigation" );
		
		$ul->setAttr ( 'class', 'ui-widget-header ui-wizzard-navigation' );
		
		//$ul->setAttr("class","navigation");
		$i = 1;
		foreach ( $this->_steps as $k => $step ) {
			$s = new WebControl ( "li" );
			$s->setText ( "<a href=\"" . ($this->_ajaxMode ? "" : "#" . $this->getId () . "-wizzard-item-$k") . "\">" . $step ["title"] . "</a>" );
			$ul->add ( $s );
			$i ++;
		}
		
		$this->add ( $ul );
		
		$rc = new WebControl ( "div" );
		$rc->setId ( $this->getId () . "-wizzard-content" );
		$rc->setAttr ( "class", "wizzard-content" );
		$ul = new WebControl ( "ul" );
		$i = 1;
		$base = $this->getConfig('base_url');
		foreach ( $this->_steps as $k => $step ) {
			$s = new WebControl ( "li" );
			$v  =array('_step'=>$k,'__t' =>time(),'__ajax'=>1);
			$s->setText ( "<a href=\"" . (isset($step['url']) || $base ? (isset($step['url']) ? URLHelper::addParam($step['url'],$v) : URLHelper::addParam($base,$v)) : "#" . $this->getId () . "-wizzard-item-$k") . "\">" . $step ["title"] . "</a>" );
			$ul->add ( $s );
			$i ++;
		}
		$rc->add($ul);
		
		$r = new WebControl ( "div" );
		//$r->setId ( $this->getId () . "-wizzard-items" );
		//$r->setAttr ( "class", "items" );
		$i = 1;
		foreach ( $this->_steps as $k => $step ) {
			$step ["content"] = isset ( $step ["content"] ) ? $step ["content"] : null;
			$s = new WebControl ( "div" );
			$s->setId ( $this->getId () . "-wizzard-item-$k" );
			$s->setAttr ( "class", "item" );
			//$s->setText ( "<h3>" . $step ["title"] . "</h3>" . $step ["content"] );
			$rc->Add ( $s );
			$i ++;
		}
		//$rc->add ( $r );
		$this->add ( $rc );
		$this->getTemplate ()->addCSSFile ( 'ui/ui-wizzard.css' );
		//$this->add($r);
	}
	
	function setJSSufix($js) {
		$this->_js = $js;
	}
	private function getVarName() {
		return $this->_varName;
	}
}
?>
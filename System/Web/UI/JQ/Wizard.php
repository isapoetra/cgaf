<?php
namespace System\Web\UI\JQ;
use System\Web\UI\Controls\WebControl;

class Wizard extends Scrollable {
	private $_steps;
	function __construct($id,  $steps) {
		parent::__construct ( $id, null );
		$this->setAttr("class","ui-wizzard tabbable  tabs-left");
		$this->setConfig ( "size", 1 );
		$this->setConfig ( "clickable", false );
		$this->_jsObj = "tabs";
		$this->_steps = $steps;
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
		
		$ul->setAttr ( 'class', 'nav nav-tabs' );
		
		// $ul->setAttr("class","navigation");
		$i = 1;
		foreach ( $this->_steps as $k => $step ) {
			$s = new WebControl ( "li" );
			$elemid = $this->getId () . '-wizzard-item-'.$k;
			$s->setText ('<a href="' . ($this->_ajaxMode ? '' :  					 
					(isset($step['url']) ? '#' : '#'. $elemid))
					. '" data-toggle="tab"'
					.(isset($step['url']) ? ' onclick="$(this).addClass(\'active\');$(\'#'.$elemid.'\').show().load(\''.\URLHelper::add($step['url'],'?__step='.$k).'\')"': '') 
					.'>' . $step ["title"] . '</a>' );
			$ul->add ( $s );
			$i ++;
		}
		
		$this->add ( $ul );
		$rc = new WebControl ( "div" );
		$rc->setId ( $this->getId () . "-wizzard-content" );
		$rc->setAttr ( "class", "tab-content" );
		
		$r = new WebControl ( "div" );
		$r->setClass('tab-content');
		
		$i = 1;
		foreach ( $this->_steps as $k => $step ) {
			$step ["content"] = isset ( $step ["content"] ) ? $step ["content"] : null;
			$s = new WebControl ( "div" );
			$s->setId ( $this->getId () . "-wizzard-item-$k" );
			$s->setAttr ( "class", "tab-pane" );
			$rc->Add ( $s );
			$i ++;
		}
		// $rc->add ( $r );
		$this->add ( $rc );
		$this->getAppOwner()->addClientAsset('cgaf/css/ui/wizzard.css' );
		// $this->add($r);
	}
	
	function setJSSufix($js) {
		$this->_js = $js;
	}
	private function getVarName() {
		return $this->_varName;
	}
}
?>
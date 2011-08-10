<?php
namespace System\Web\UI\Controls;
class ThumbnailItem extends WebControl  {
	private $_backgroundImage;
	function __construct($title,$image,$action){
		parent::__construct('a',true,array(
			'href'=>$action
		));
		$this->setTitle($title);
		$this->_backgroundImage = $image;
	}
	function getBackgroundImage() {
		return $this->_backgroundImage;
	}
	function setAction($action) {
		$this->setAttr('href',$action);
	}
	function getAction() {
		return $this->getAttr('href');
	}
	function Render ($return = false) {
		$retval = '<a class="thumbnail-item" href="'.$this->action.'">';
		$retval .= '<div class="thumbnail-item-wrapper">';
		$retval .= '<img src="'.$this->_backgroundImage.'"/>';
		$retval .= '</div>';
		$retval .= '<span class="title">'.$this->getTitle().'</span>';
		$retval .= '</a>';
		return $retval;
	}
}
class Thumbnail extends WebControl {
	private $_thumContainer;
	function __construct() {
		parent::__construct('div',false,array('class'=>'thumbnail'));

		//$this->addChild(new HTMLControl('div',true,array('class'=>'thumbnail-scrollbar')));
		$this->_thumContainer = $this->addChild(new HTMLControl('div',false,array('class'=>'thumbnail-container')));
	}
	function prepareRender() {
		$ss = <<< EOT
.thumbnail {
	display:block;
	border:1px solid red;
	height:250px;
	overflow: hidden;
}
			
EOT;
		$id = $this->getId();
		$script = <<< EOT
$('#{$id}').thumbnail();
EOT;
		$this->getAppOwner()->addClientScript($script);
		$this->getAppOwner()->addStyleSheet($ss);
		return true;
	}
	function addChild($c) {
		if ($this->_thumContainer) {
			return $this->_thumContainer->addChild($c);
		}
		return parent::addChild($c);
	}
}
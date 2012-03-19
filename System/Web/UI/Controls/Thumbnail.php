<?php
namespace System\Web\UI\Controls;
use System\Web\JS\CGAFJS;
use Convert;
class ThumbnailItem extends WebControl {
	private $_backgroundImage;
	private $_actions = array ();
	private $_description;
	private $width='160';
	private $height='120';
	private $_action;
	private $_stitle;
	function __construct($title, $image, $action) {
		parent::__construct ( 'div', true,array('class'=>'thumbnail') );
		$this->_stitle = $title;
		$this->_action = $action;
		$this->_backgroundImage = $image;
	}
	function setDescription($value) {
		$this->_description = $value;
	}
	function addAction($action) {
		$this->_actions [] = $action;
	}
	function getBackgroundImage() {
		return $this->_backgroundImage;
	}
	function setAction($action) {
		$this->setAttr ( 'href', $action );
	}
	function getAction() {
		return $this->getAttr ( 'href' );
	}
	function prepareRender() {
		$link = new Anchor ( $this->_action, '' );
		$link->setAttr('class','thumbnail');
		$link->addChild ( '<img src="' . $this->_backgroundImage . '" width="'.$this->width.'px" height="'.$this->height.'px"/>' );
		$this->addChild ( $link );

		$cap = new WebControl ( 'div', false, array (
				'class' => 'caption'
		) );
		if ($this->_title) $cap->AddChild('<h5>'.$this->_title.'</h5>');
		if ($this->_description) $cap->addChild('<div>'.$this->_description.'</div>');
		if ($this->_actions) $cap->addChild('<div class="actions">'.\Utils::toString($this->_actions).'</div>');
		if ($cap->hasChild()) $this->addChild ( $cap );
	}
}
class Thumbnail extends WebControl {
	private $_itemClass='span2';
	function __construct() {

		parent::__construct ( 'ul', false, array (
				'class' => 'thumbnails'
		) );
	}
	function setItemClass($value) {
		$this->_itemClass = $value;
	}
	function renderItems() {
		// $c = $this->_childs;
		$retval = '';
		foreach ( $this->_childs as $c ) {
			$retval .= '<li class="'.$this->_itemClass.'">';
			$retval .= \Convert::toString ( $c );
			$retval .= '</li>';
		}
		return $retval;
	}
}

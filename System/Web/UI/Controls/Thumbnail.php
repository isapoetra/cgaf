<?php
namespace System\Web\UI\Controls;
use System\Web\Utils\HTMLUtils;

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
	private $_imgW;
	private $_imgH;
	private $_linkAttr;
	private $_link;
	function __construct($title, $image, $action) {
		parent::__construct ( 'div', true,array('class'=>'thumbnail') );
		$this->_stitle = $title;
		$this->_action = $action;
		$this->_backgroundImage = $image;
		$this->_link = new Anchor ( $this->_action, '');

		$this->_link->setAttr('class','thumbnail');
	}
	function setDescription($value) {
		$this->_description = $value;
	}
	function addAction($action) {
		$this->_actions [] = $action;
	}
	function setLinkAttr($k,$v=null) {
		$this->_link->setAttr($k,$v);
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
	function setImageSize($w,$h) {
		$this->_imgW=$w;
		$this->_imgH=$h;
	}
	function prepareRender() {
		$this->_link->setAction($this->_action);
		$imgAttrs = '';
		if ($this->_imgW && $this->_imgH) {
			if (\Strings::BeginWith($this->_backgroundImage,array('http:','https'))) {
				$this->_backgroundImage= \URLHelper::add($this->_backgroundImage,'?size='.$this->_imgW.'x'.$this->_imgH);
			}
			$imgAttrs = array('style'=>'width:'.$this->_imgW.'px;height='.$this->_imgH,
					'width'=>$this->_imgW.'px',
					'height'=>$this->_imgH.'px');
		}
		if ($this->_link->getAttr('href')) {
		$this->_link->addChild ( '<img src="' . $this->_backgroundImage . '" '.HTMLUtils::renderAttr($imgAttrs).'/>' );
		$this->addChild ( $this->_link );
		}else{
			$this->addChild ( '<img src="' . $this->_backgroundImage . '" '.HTMLUtils::renderAttr($imgAttrs).'/>' );
		}

		$cap = new WebControl ( 'div', false, array (
				'class' => 'caption'
		) );
		if ($this->_stitle) $cap->AddChild('<h5>'.$this->_stitle.'</h5>');
		if ($this->_description) $cap->addChild('<div>'.$this->_description.'</div>');
		if ($this->_actions) $cap->addChild('<div class="actions">'.\Utils::toString($this->_actions).'</div>');
		if ($cap->hasChild()) $this->addChild ( $cap );
	}
}
class Thumbnail extends WebControl {
	private $_itemClass='span2';
	private $_size;
	function __construct() {
		parent::__construct ( 'ul', false, array (
				'class' => 'thumbnails'
		) );
	}
	function setImageSize($w,$h) {
		$this->_size =array('w'=>$w,'h'=>$h);
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
	function prepareRender() {
		parent::prepareRender();
		if ($this->_size) {
			foreach ($this->_childs as $v) {
				$v->setImageSize($this->_size['w'],$this->_size['h']);
			}
		}
	}
}

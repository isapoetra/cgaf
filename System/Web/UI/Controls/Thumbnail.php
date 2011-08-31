<?php
namespace System\Web\UI\Controls;
use System\Web\JS\CGAFJS;
use \Convert;
class ThumbnailItem extends WebControl {
	private $_backgroundImage;
	private $_actions = array();
	private $_description;
	function __construct($title, $image, $action) {
		parent::__construct('a', true, array('href' => $action));
		$this->setTitle($title);
		$this->_backgroundImage = $image;
	}
	function setDescription($value) {
		$this->_description = $value;
	}
	function addAction($action) {
		$this->_actions[] = $action;
	}
	function getBackgroundImage() {
		return $this->_backgroundImage;
	}
	function setAction($action) {
		$this->setAttr('href', $action);
	}
	function getAction() {
		return $this->getAttr('href');
	}
	function Render($return = false) {
		$retval = '<div class="thumbnail-item"><div>';
		$retval .= '<div class="thumbnail-item-wrapper fill-parent" style="background-image:url(' . $this->_backgroundImage . ')">';
		$retval .= '<div class="thumbnail-content"><div>';
		foreach ($this->_childs as $c) {
			$retval .= Convert::toString($c);
		}
		$retval .= '</div></div>';
		if ($this->_description) {
			$retval .= '<div class="descr"><span>' . $this->_description . '</span></div>';
		}
		if ($this->_actions) {
			$retval .= '<div class="action">';
			foreach ($this->_actions as $c) {
				$retval .= Convert::toString($c);
			}
			$retval .= '</div>';
		}
		$retval .= '</div>';
		$retval .= '<a href="' . $this->action . '" class="title"><span>' . $this->getTitle() . '</span></a>';
		$retval .= '</div></div>';
		return $retval;
	}
}
class Thumbnail extends WebControl {
	private $_thumContainer;
	function __construct() {
		parent::__construct('div', false, array('class' => 'thumbnail'));
		//$this->addChild(new HTMLControl('div',true,array('class'=>'thumbnail-scrollbar')));
		$this->_thumContainer = $this->addChild(new HTMLControl('div', false, array('class' => 'thumbnail-container')));
	}
	function prepareRender() {
		CGAFJS::loadPlugin('thumbnail', true);
		$thumbcss = $this->getAppOwner()->getLiveAsset('thumbnail.css', 'cgaf');
		$this->getAppOwner()->addClientAsset($thumbcss);
		$id = $this->getId();
		//TODO MOVe to imagescroll.js
		$script = <<< EOT
function imgscroll(el,config) {
	var config = $.extend({
		itemWidth: 240,
		itemHeight: 150,
		itemURL : null
	},config || {});
	var el =this.el = $(el);
	var litem=0;
	var container=el.find('.img-container');
	el.css({
		overflow:'hidden'
	});
	var cl = container.children().length;
	container.css({
		position :'absolute',
		overflow :'hidden',
		height : config.itemHeight,
		width:cl*config.itemWidth
	});
	l=0;
	var lnav = container.parent().find('.left-nav');
	var rnav = container.parent().find('.right-nav');
	container.children().each(function(){
		$(this).css({
			width:config.itemWidth,
			height:config.itemHeight,
			float:'left',
		});
		l+=200;
	});
	function setActiveItem(item) {
		var mpx = (item  *config.itemWidth*-1);
		var cl = container.children().length;
		var mw = (cl-1) *config.itemWidth;
		var inleft =false;
		rnav.show();
		lnav.show();
		if (mpx>=0) {
			lnav.hide();
		}else if(item >= cl-1) {
			mpx = mw*-1;
			item = cl-1;
			rnav.hide();
		}
		container.stop().animate({
			left:  mpx
		});
		litem=item;
	}
	el.find('.left-nav').click(function(e) {
		e.preventDefault();
		setActiveItem(litem-1);
	});
	el.find('.right-nav').click(function(e) {
		e.preventDefault();
		setActiveItem(litem+1);
	});
	setActiveItem(0);
}
$('#{$id}').thumbnail();
EOT;
		$ss = <<<EOT
		.img-item img {
			width:100%;
			height:100%;
		}
EOT;
		$this->getAppOwner()->addStyleSheet($ss);
		$this->getAppOwner()->addClientScript($script);
		return true;
	}
	function addChild($c) {
		if ($this->_thumContainer) {
			return $this->_thumContainer->addChild($c);
		}
		return parent::addChild($c);
	}
}

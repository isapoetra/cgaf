<?php
namespace System\Web\UI\Controls;
use System\Web\JS\CGAFJS;
use \Convert;
class ThumbnailItem extends WebControl {
	private $_backgroundImage;
	private $_actions = array();
	private $_description;
	public $width;
	public $height;
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
		//CGAFJS::loadPlugin('plugin.scrollbar',true);
		$retval = '<div class="thumbnail-item" style="width:'.$this->width.'px;height:'.$this->height.'px;position:relative"><div>';
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
		$retval .= '<a href="' . $this->action . '" class="title" title="'.$this->getTitle().'"><span>' . $this->getTitle() . '</span></a>';
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

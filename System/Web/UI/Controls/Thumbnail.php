<?php
namespace System\Web\UI\Controls;
use System\Web\JS\CGAFJS;
use Convert;
class ThumbnailItem extends WebControl {
	private $_backgroundImage;
	private $_actions = array ();
	private $_description;
	public $width;
	public $height;
	private $_action;
	private $_stitle;
	function __construct($title, $image, $action) {
		parent::__construct ( 'div', true );
		$this->_stitle = $title;
		$this->_action = $action;
		// array('href' => $action)
		// $this->setTitle($title);
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
		$link->addChild ( '<img src="' . $this->_backgroundImage . '"/>' );
		$this->addChild ( $link );
		$cap = new WebControl ( 'div', false, array (
				'class' => 'caption' 
		) );
		$cap->AddChild('<h5>'.$this->_title.'</h5>');
		$cap->addChild('<div>'.$this->_description.'</div>');
		$cap->addChild('<div class="actions">'.\Utils::toString($this->_actions).'</div>');
		$this->addChild ( $cap );
	}
	/*
	 * function Render($return = false) {
	 * //CGAFJS::loadPlugin('plugin.scrollbar',true); $retval = '<div
	 * class="thumbnail-item"
	 * style="width:'.$this->width.'px;height:'.$this->height.'px;position:relative"><div>';
	 * $retval .= '<div class="thumbnail-item-wrapper fill-parent"
	 * style="background-image:url(' . $this->_backgroundImage . ')">'; $retval
	 * .= '<div class="thumbnail-content"><div>'; foreach ($this->_childs as $c)
	 * { $retval .= Convert::toString($c); } $retval .= '</div></div>'; if
	 * ($this->_description) { $retval .= '<div class="descr"><span>' .
	 * $this->_description . '</span></div>'; } if ($this->_actions) { $retval
	 * .= '<div class="action">'; foreach ($this->_actions as $c) { $retval .=
	 * Convert::toString($c); } $retval .= '</div>'; } $retval .= '</div>';
	 * $retval .= '<a href="' . $this->action . '" class="item-title"
	 * title="'.$this->getTitle().'"><span>' . $this->getTitle() .
	 * '</span></a>'; $retval .= '</div></div>'; return $retval; }
	 */
}
class Thumbnail extends WebControl {
  private $_itemClass='span2';
	function __construct() {

		parent::__construct ( 'ul', false, array (
				'class' => 'thumbnails' 
		) );
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
		CGAFJS::loadPlugin ( 'thumbnail', true );
		// $thumbcss = $this->getAppOwner()->getLiveAsset('thumbnail.css',
		// 'cgaf');
		// $this->getAppOwner()->addClientAsset($thumbcss);
		
		$id = $this->getId ();
		// TODO MOVe to imagescroll.js
		/*
		 * $script = <<< EOT $('#{$id}').thumbnail(); EOT; $ss = <<<EOT
		 * .img-item img { width:100%; height:100%; } EOT;
		 * $this->getAppOwner()->addStyleSheet($ss);
		 * $this->getAppOwner()->addClientScript($script);
		 */
		return true;
	}
	function addChild($c) {
		return parent::addChild ( $c );
	}
}

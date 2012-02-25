<?php
namespace System\Web\UI\JQ;
use System\JSON\JSON;

class Carousel extends Control {
	private $_data;
	function setData($data) {
		$this->_data = $data;
	}
	function preRender() {
		$tpl = $this->getTemplate ();
		$tpl->addCSSFile ( 'js/carousel/carousel.css' );
		$tpl->addAsset ( 'carousel/jquery.carousel.js' );
	}
	function RenderScript($return = false) {
		$c = parent::RenderScript ( true );
		$tpl = $this->getTemplate ();
		$configs = JSON::encodeConfig ( $this->_configs );
		$script = <<<EOT
		$('#{$this->getId()}').carousel($configs);
EOT;
		$tpl->addClientScript ( $script );
		return $c;
	}
	function renderJSON($return = false) {
		
		return JSON::encode ( $this->_data );
	}
	function Render($return = false) {
		$a = \Request::get ( '_a' );
		switch ($a) {
			case 'data' :
				return $this->renderData ( true );
		}
		return parent::Render ( $return );
	}
}
<?php
namespace System\Web\JS;
class JSFunc implements \IRenderable {
	private $_text = null;
	function __construct($text) {
		$this->_text = $text;
	}
	function Render($return = false, &$handle = false) {
		if ($this->_text) {
			$handle = true;
			return $this->_text;
		}
	}
}

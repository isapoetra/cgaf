<?php

class JSEngineJQ extends JSBaseEngine {

	function __construct($appOwner) {
		parent::__construct ( $appOwner, 'jQuery', 'latest', '1.4' );
	}


	protected function getJSAsset() {
		$prefix = strtolower ( $this->_baseConfig );
		$assets = array (
		$prefix . '.js');
		if ($this->getConfig('useui',true)) {
			$assets[] = 'ui/'.$prefix . '-ui.js';
			$assets[] ='themes/base/'.$prefix.'-ui.css';
		}
		return $assets;
	}


}
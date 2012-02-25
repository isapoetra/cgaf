<?php
namespace System\Web\JS\Engine;
use Utils;

class jQuery extends AbstractJSEngine {
	private $_loaded = false;
	function __construct($appOwner) {
		parent::__construct ( $appOwner, 'jQuery', $appOwner->getConfig ( 'app.js.jQuery.version' ), $appOwner->getConfig ( 'app.js.jQuery.compat' ) );
	}
	function loadUI($direct = true) {
		if ($this->_loaded)
			return;
		if (! $this->getConfig ( 'ui.enabled', false )) {
			return array ();
		}
		$this->_loaded = true;
		$assets = array ();
		$ui = 'JQuery-UI/' . $this->getConfig ( 'ui.version', '1.8.17' ) . DS;
		$assets [] = 'jquery-ui.js';
		$assets [] = 'themes/base/jquery.ui.base.css';
		if ($theme = $this->_appOwner->getUserConfig ( 'ui.themes', $this->_appOwner->getConfig ( 'ui.themes', 'ui-lightness' ) )) {
			$assets [] = 'themes/' . $theme . '/jquery-ui.css';
		}
		$retval = array ();
		foreach ( $assets as $asset ) {			
			$r = $this->getAsset ( $ui . $asset, null, false );			
			if (! $r) {
				$r = $this->getAsset ( $asset, null );
			}
			$retval [] = $r;
			//
		}
		if ($direct) {
			$this->_appOwner->addClientAsset ( $retval );
		}
		return $retval;
	}
	protected function getJSAsset() {
		$prefix = strtolower ( $this->_baseConfig );
		$assets = array ();
		$ui = array ();
		if ($this->_useui && ! \Request::isMobile ()) {
			$ui = $this->loadUI ( false );
		}
		\Utils::arrayMerge ( $assets, $ui );
		return $assets;
	}
}

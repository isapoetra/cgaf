<?php
namespace System\Web\JS\Engine;
use Utils;
use \System\Applications\IApplication;
class jQuery extends AbstractJSEngine {
	private $_loaded = false;
	function __construct(IApplication $appOwner) {
		parent::__construct ( $appOwner, 'jQuery', $appOwner->getConfig ( 'app.js.jQuery.version', '1.7.1' ), $appOwner->getConfig ( 'app.js.jQuery.compat' ) );
	}
	function loadUI($direct = true) {
		if ($this->_loaded)
			return array ();
		if (! $this->getConfig ( 'ui.enabled', true )) {
			return array ();
		}
		$this->_loaded = true;
		$assets = array ();
		$ui = 'jQuery-UI/' . $this->getConfig ( 'ui.version', '1.8.17' ) . DS;
		$assets [] = 'jquery-ui.js';
		$assets [] = 'themes/base/jquery.ui.base.css';
		$theme = $this->_appOwner->getUserConfig ( 'ui.themes', $this->_appOwner->getConfig ( 'ui.themes', 'ui-lightness' ) );
		if ($theme) {
			$assets [] = 'themes/' . $theme . '/jquery-ui.css';
		}
		$retval = array ();
		foreach ( $assets as $asset ) {
			$r = $this->getAsset ( $ui . $asset, null, true );
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
		$assets = array (
				'jquery-' . $this->_defaultVersion . '.js',
				'plugins/jquery.url.js'
		);
		$ui = array ();
		if ($this->_useui && ! \Request::isMobile ()) {
			$ui = $this->loadUI ( false );
		}
		\Utils::arrayMerge ( $assets, $ui );
		return $assets;
	}
}

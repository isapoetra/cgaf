<?php
namespace System\Web\JS\Engine;
use \Utils;
class jQuery extends AbstractJSEngine {
	function __construct($appOwner) {
		parent::__construct($appOwner, 'jQuery',
				$appOwner->getConfig('js.jQuery.version', '1.6.2'), '1.4');
	}
	function loadUI($direct = true) {
		static $loaded;
		if ($loaded)
			return;
		$loaded = true;
		$assets = array();
		$ui = 'jQuery-UI/' . $this->getConfig('ui.version', '1.8.15');
		$assets[] = '/jquery-ui.js';
		$assets[] =  '/themes/base/jquery-ui.css';
		if ($theme = $this->_appOwner
				->getUserConfig('ui.themes',
						$this->_appOwner->getConfig('ui.themes'))) {
			$assets[] =  '/themes/' . $theme . '/jquery-ui.css';
		}
		$retval = array();
		foreach ($assets as $asset) {
			$r = $this->getAsset($ui.$asset,null,false);
			if (!$r) {
				$r = $this->getAsset($asset,null);
			}
			$retval[] = $r;
			//
		}
		if ($direct) {
			$this->_appOwner->addClientAsset($retval);
		}
		return $retval;
	}
	protected function getJSAsset() {
		$prefix = strtolower($this->_baseConfig);
		$assets = array();
		if ($this->_useui) {
			\Utils::arrayMerge($assets, $this->loadUI(false));
		}
		return $assets;
	}
}

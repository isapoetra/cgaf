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
		$assets[] = $ui . '/jquery-ui.js';
		$assets[] = $ui . '/themes/base/jquery-ui.css';
		if ($theme = $this->_appOwner
				->getUserConfig('ui.themes',
						$this->_appOwner->getConfig('ui.themes'))) {
			$assets[] = $ui . '/themes/' . $theme . '/jquery-ui.css';
		}
		if ($direct) {
			$this->_appOwner->addClientAsset($this->getAsset($assets));
		}
		return $assets;
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

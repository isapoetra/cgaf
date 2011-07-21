<?php

abstract class JSBaseEngine {
	protected $_appOwner;
	protected $_info;
	protected $_baseConfig;
	protected $_jqInfo;
	protected $_searchPath;

	function __construct($appOwner, $baseConfig, $defaultVersion, $defaultCompat = null) {
		$this->_appOwner = $appOwner;
		$this->_baseConfig = $baseConfig;
		$this->_jqInfo = array (
				'version' => $this->getConfig ( 'app.jQuery.version', 'latest' ),
				'compat' => $this->getConfig ( 'app.jQuery.compat', '1.4' ) );
		$this->_info = array (
				'version' => $this->getConfig ( 'app.' . $baseConfig . '.version', $defaultVersion ),
				'compat' => $this->getConfig ( 'app.' . $baseConfig . '.compat', $defaultCompat ) );
		$this->_id = Utils::generateId ( 'template_' );

	}

	protected function getSearchPath() {
		if (! $this->_searchPath) {
			$this->_searchPath = array (
			$this->_baseConfig . '/' . $this->_info ['version'] . '/' );
			if ($this->_info ['compat']) {
				$this->_searchPath [] = $this->_baseConfig . '/' . $this->_info ['compat'] . '/';
			}
			$this->_searchPath [] = $this->_baseConfig . '/';
			$this->_searchPath [] = '';
		}
		return $this->_searchPath;
	}

	protected function getConfig($configName, $def = null) {
		return $this->_appOwner->getConfig ( 'app.js.' . $this->_baseConfig . '.' . $configName, $def );
	}
	/**
	 *
	 * Enter description here ...
	 * @throws AssetException
	 * @return ArrayIterator
	 */
	protected abstract function getJSAsset() ;
	function getAsset($asset) {
		if (is_array ( $asset )) {
			$retval = array ();
			foreach ( $asset as $k => $v ) {
				$asset = $this->getAsset ( $v );
				if ($asset) {
					$retval [$k] = $asset;
				}
			}
			return $retval;
		}
		$old = $asset;
		$spath = $this->getSearchPath();
		$cpath = array();
		foreach ( $spath as $alt ) {
			$cpath[]= $alt . $asset ;
			$cpath[] = 'js/'.$alt . $asset;

		}
		foreach ( $spath as $alt ) {
			$cpath[]= $alt . $old ;
			$cpath[] = 'js/'.$alt . $old;
		}

		foreach ($cpath as $value) {
			$r = $this->_appOwner->getAsset ($value );
			if ($r) {
				return $r;
			}
		}

		$retval =  $this->_appOwner->getAsset ( $old );
		if (!$retval && CGAF_DEBUG) {
			pp($old);
			pp($spath);
			ppd($cpath);
			//throw new AssetException($old);
		}
		return $retval;
	}

	function getSourcePath() {
		return 'js/' . $this->_baseConfig . '/' . $this->_info ['version'] . '/';
	}

	function initialize(IWebApplication &$app) {

		if (! Request::isAJAXRequest ()) {
			$theme = $this->getConfig ( 'themes', 'default' );
			$version = $this->_info ['version'];

			$assets = array(
			'modernizr.js',
			'jquery.js',
			'cgaf/cgaf.js'
			);
			if ($this->getConfig("useui")) {
				$assets[]="cgaf/ui/cgaf.ui.js";
			}

			$assets=array_merge($assets,$this->getJSAsset());

			if (CGAF_DEBUG) {
				$assets [] = 'debug.js';
				$assets [] = 'debug.css';
			}

			$retval = array ();

			$app->addClientAsset($this->getAsset ( $assets ) );
			if (! Request::isDataRequest ()) {
				CGAFJS::setConfig('appurl',Utils::PathToLive ( $this->_appOwner->getLivePath(null)));
				CGAFJS::setConfig('asseturl',ASSET_URL);
				CGAFJS::setConfig('jq.version', $this->_jqInfo['version']);
				CGAFJS::setConfig('baseurl', BASE_URL);
				CGAFJS::setConfig('appurl', APP_URL);
				CGAFJS::loadStyleSheet(ASSET_URL.'css/cgaf.css');

			}
		}

	}

	function renderScript($s) {
		$s = is_array($s) ? implode(';', $s) : $s;
		$s = CGAF_DEBUG ? $s : JSUtils::Pack ( $s );
		return <<< EOT
				<script type="text/javascript" language="javascript">
				$s
				</script>
EOT;
	}
}
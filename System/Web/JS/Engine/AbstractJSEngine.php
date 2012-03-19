<?php
namespace System\Web\JS\Engine;
use System\Web\JS\JSUtils;
use System\MVC\Application;
use Utils, CGAF, Request, System\Web\JS\CGAFJS;
use System\Exceptions\AssetException;
use System\Applications\IApplication;
abstract class AbstractJSEngine implements \IJSEngine {
	protected $_appOwner;
	protected $_info;
	protected $_baseConfig;
	protected $_jqInfo;
	protected $_searchPath;
	protected $_useui;
	protected $_defaultVersion;
	function __construct(IApplication $appOwner, $baseConfig, $defaultVersion, $defaultCompat = null) {
		$this->_appOwner = $appOwner;
		$this->_baseConfig = $baseConfig;
		$this->_defaultVersion = $defaultVersion;
		$this->_info = array (
				'version' => $this->getConfig ( 'app.' . $baseConfig . '.version', $defaultVersion ),
				'compat' => $this->getConfig ( 'app.' . $baseConfig . '.compat', $defaultCompat )
		);
		$this->_id = Utils::generateId ( 'template_' );
		$this->_useui = $this->getConfig ( 'useui', true );
	}
	function getInfo() {
		return $this->_info;
	}
	protected function getSearchPath() {
		if (! $this->_searchPath) {
			$this->_searchPath = array ();
			$this->_searchPath [] = $this->_baseConfig . '/' . $this->_info ['version'] . '/';
			if (isset ( $this->_info ['compat'] )) {
				$this->_searchPath [] = $this->_baseConfig . '/' . $this->_info ['compat'] . '/';
			}
			$this->_searchPath [] = $this->_baseConfig . '/';
			$this->_searchPath [] = '';
		}
		return $this->_searchPath;
	}
	protected function getConfigs($configName, $def = null) {
		return $this->_appOwner->getConfigs ( 'js.' . $this->_baseConfig . '.' . $configName, $def );
	}
	protected function getConfig($configName, $def = null) {
		return $this->_appOwner->getConfig ( 'js.' . $this->_baseConfig . '.' . $configName, $def );
	}

	protected abstract function getJSAsset();
	function getAsset($asset, $prefix = null, $throw = true) {
		if (is_array ( $asset )) {
			$retval = array ();
			foreach ( $asset as $k => $v ) {
				$asset = $this->getAsset ( $v, $prefix );
				if ($asset) {
					$retval [$k] = $asset;
				}
			}
			return $retval;
		}
		if (is_file ( $asset )) {
			return $asset;
		}
		$old = $asset;
		$spath = $this->getSearchPath ();
		$cpath = array ();
		foreach ( $spath as $alt ) {
			$cpath [] = $alt . $prefix . DS . $asset;
			$cpath [] = 'js/' . $alt . $prefix . DS . $asset;
		}
		foreach ( $spath as $alt ) {
			$cpath [] = $alt . $old;
			$cpath [] = 'js/' . $alt . $prefix . DS . $old;
		}
		foreach ( $cpath as $value ) {
			$r = $this->_appOwner->getLiveAsset ( $value );
			if ($r) {
				return $r;
			}
		}
		$r = $this->_appOwner->getLiveAsset ( $old );
		if (! $r) {
			if ($throw) {
				ppd($cpath);
				throw new AssetException ( "unable to get asset " . $old );
			}
		}
		return $r;
	}
	function getSourcePath() {
		return 'js/' . $this->_baseConfig . '/' . $this->_info ['version'] . '/';
	}
	protected function initJQuery() {
	}
	function initialize(IApplication &$app) {
		if (Request::isAJAXRequest ())
			return;
		//$appOwner = $this->_appOwner;
		//$theme = $this->getConfig ( 'themes', 'default' );
		//$version = $this->_info ['version'];
		$assets = $this->getJSAsset ();
		//$retval = array ();
		$app->addClientAsset ( $this->getAsset ( $assets ) );
	}
	function renderScript($s) {
		$s = is_array ( $s ) ? implode ( ';', $s ) : $s;
		$s = CGAF_DEBUG ? $s : JSUtils::Pack ( $s );
		return <<< EOT
				<script type="text/javascript" language="javascript">
		$s
				</script>
EOT;
	}
}

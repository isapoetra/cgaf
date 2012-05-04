<?php
namespace System\Web\JS;
use System\Exceptions\AccessDeniedException;

use System\Web\JS\Engine\jQuery;
use System\MVC\Application;
use System\JSON\JSON;
use Request;
use CGAF;
use Utils;

final class CGAFJS {
	const _JSInstance = 'cgaf';
	private static $_jsToLoad = array ();
	private static $_configs = array ();
	private static $_css = array ();
	private static $_plugins = array ();
	private static $_toolbars = array ();
	/**
	 * @var \System\MVC\Application
	 */
	private static $_appOwner;
	/**
	 * @var jQuery
	 */
	private static $_jq;
	private static $_pluginsLoader = array ();
	private static $_initialized;
	public static function initialize(Application $appOwner = null, $force = false) {
		if (! $appOwner) {
			$appOwner = \AppManager::getInstance ();
		}
		self::$_appOwner = $appOwner;
		if (! $force) {
			if (self::$_initialized || Request::isAJAXRequest ())
				return;
		}
		self::$_initialized = true;
		$appOwner->clearClient ();
		self::$_jq = $jq = new jQuery ( $appOwner );
		$fv = $appOwner->getConfig ( 'js.fancybox.version', '1.3.4' );
		self::$_pluginsLoader = array (
				'fancybox' => array (
						'configs' => $appOwner->getConfig ( 'js.fancybox.configs', array () ),
						'assets' => array (
								'js/jQuery/plugins/fancybox/' . $fv . '/jquery.fancybox-' . $fv . '.css',
								'js/jQuery/plugins/fancybox/' . $fv . '/jquery.fancybox-' . $fv . '.js'
						)
				)
		);

		if (! Request::isDataRequest ()) {
			$info = $jq->getInfo ();
			self::setConfig ( 'asseturl', ASSET_URL );
			self::setConfig ( 'jq.version', $info ['version'] );
			self::setConfig ( 'jq.compat', $info ['compat'] );
			self::setConfig ( 'baseurl', BASE_URL );
			self::setConfig ( 'appurl', \AppManager::getInstance ()->getAppURL () );
			self::setConfig ( 'appid',\AppManager::getInstance ()->getAppId());
		}
		$assets =array();
		if (\Request::isMobile()) {
			$assets [] ='jQuery/jquery-mobile/jquery.mobile.structure.css';
			$assets [] ='jQuery/jquery-mobile/jquery.mobile.theme.css';
		}
		if ($appOwner->getConfig ( 'js.bootstrap.enabled', true )) {
			$assets[]= 'bootstrap/css/bootstrap.css';

			$assets[]= 'cgaf/css/cgaf-all.css';
			$assets[]= 'bootstrap/js/bootstrap.js';
			$plugins = $appOwner->getConfigs ( 'js.bootstrap.plugins', array () );
			foreach ( $plugins as $p ) {
				$assets[]= 'bootstrap/js/' . $p ;
			}
		}
		$assets[] = 'cgaf/cgaf.js';
		if (! \Request::isMobile ()) {
			$assets[] = 'cgaf/cgaf-jq.js';
			if (CGAF_DEBUG) {
				$assets [] = 'cgaf/debug.js';
				$assets [] = 'cgaf/css/debug.css';
			}

		} else {
			$assets [] ='cgaf/mobile/cgaf.js';
			$assets[]= 'bootstrap/css/bootstrap-responsive.css';
			$assets [] ='jQuery/jquery-mobile/jquery.mobile.js';
			$assets [] ='cgaf/css/cgaf-mobile.css';
		}
		$plugins = CGAF::getConfigs ( 'cgaf.js.plugins' );
		if ($plugins) {
			Utils::arrayMerge ( $assets, $jq->getAsset ( $plugins, 'plugins' ) );
		}

		$jq->initialize ( $appOwner );

		try {

			Utils::arrayMerge ( $assets, $jq->loadUI ( false ) );
			self::$_appOwner->addClientAsset ( $jq->getAsset ( $assets ) );
		}catch (\Exception $e){
			ppd($e);
			if (CGAF_DEBUG) {
				ppd($e);
			}
		}
		$plugins = null;
		try {
			if (self::$_appOwner->getController ()) {
				$plugins = CGAF::getConfigs ( 'js' . self::$_appOwner->getController ()->getControllerName () . '.plugins' );
			}
		} catch (AccessDeniedException $e) {
			throw $e;
		}catch ( \Exception $e ) {
			\Logger::Warning($e->getMessage());
		}
		if ($plugins) {
			self::$_appOwner->addClientAsset ( $jq->getAsset ( $plugins, 'plugins' ) );
		}
		return true;
	}
	public static function addJQAsset($asset) {
		if (! self::$_appOwner) {
			return null;
		}
		$asset = self::$_jq->getAsset ( $asset );
		return self::$_appOwner->addClientAsset ( $asset );
	}
	public static function loadUI() {
		if (self::$_jq) {

			self::$_jq->loadUI ();
		}
	}
	private static function getAppOwner() {
		return \AppManager::getInstance ();
	}
	public static function getJSToLoad() {
		return self::$_jsToLoad;
	}
	public static function setConfig($configName, $value) {
		self::$_configs [$configName] = $value;
		// $arg = func_get_args();
		// self::callMethod('setConfig',$arg);// ->addClientScript (
		// 'cgaf.setConfig(\'appurl\',\'' . . '\');');
	}
	private static function callMethod($name, $args) {
		$script = self::_JSInstance . '.' . $name . '(';
		$arg = array ();
		foreach ( $args as $v ) {
			$arg [] = JSON::encode ( $v );
		}
		$script .= implode ( ',', $arg ) . ')';
		self::getAppOwner ()->addClientScript ( $script );
	}
	public static function loadStyleSheet($stylesheet) {
		self::$_css [] = $stylesheet;
	}
	public static function loadScript($js) {
		if (! Utils::isLive ( $js )) {
			$js = \AppManager::getInstance ()->getLiveAsset ( $js );
		}
		if ($js) {
			self::$_jsToLoad [] = $js;
		}
	}
	public static function loadPluginAssets($plugin, $direct = false) {
		$plugin = self::getPluginURL ( $plugin );
		self::loadStyleSheet($plugin);
	}
	public static function loadPlugin($plugin, $direct = false) {
		self::initialize ();
		if (! self::$_appOwner) {
			ppd ( self::$_plugins );
			return;
		}
		if (is_array($plugin)) {
			foreach($plugin as $p) {
				self::loadPlugin($p,$direct);
			}
			return;
		}
		if (isset ( self::$_pluginsLoader [$plugin] )) {
			$assets = self::$_pluginsLoader [$plugin] ['assets'];
			if ($direct) {
				self::$_appOwner->addClientAsset ( $assets );
			}
			return; // ppd(self::$_pluginsLoader);
		}
		if ($direct) {
			$plugin = self::getPluginURL ( $plugin );

			self::$_appOwner->addClientAsset ( $plugin );
			return;
		}
		if (! in_array ( $plugin, self::$_plugins )) {
			self::$_plugins [] = $plugin;
		}
	}
	public static function addToolbar($id, $title = null, $action = null) {
		self::$_toolbars [] = array (
				'id' => $id,
				'title' => $title,
				'action' => $action
		);
	}
	public static function getPluginURL($pluginName) {
		if (! self::$_jq) {
			return null;
		}
		$ext = \Utils::getFileExt ( $pluginName );
		if (! $ext || strlen ( $ext ) > 5) {
			$ext = '.js';
		} else {
			$ext = '';
		}
		return self::$_jq->getAsset ( $pluginName . $ext, 'plugins' );
	}
	public static function Render($s) {
		$s = is_array ( $s ) ? implode ( ';', $s ) : $s;
		// $s = CGAF_DEBUG ? $s : JSUtils::Pack($s);
		$json = JSON::encode ( self::getJSToLoad () );
		$config = null;

		if (count ( self::$_configs )) {
			$config = self::_JSInstance . '.setConfig(' . JSON::encode ( self::$_configs ) . ');';
		}
		//pp(JSON::encode ( self::$_css ) );
		//ppd(self::$_css);
		$css = count ( self::$_css ) ? self::_JSInstance . '.loadStyleSheet(' . JSON::encode ( self::$_css ) . ');' : null;

		if (count ( self::$_plugins )) {
			$plugin = PHP_EOL . self::_JSInstance . '.loadJQPlugin(' . JSON::encode ( self::$_plugins ) . ',function(){';
		} else {
			$plugin = null;
		}
		$retval = array ();
		if (! Request::isAJAXRequest ()) {
			$retval [] = 'if (typeof(jQuery) !== \'undefined\') {';
			$retval [] = '(function($) { ';
			$retval [] = 'if (!$) { $=jQuery;}';
		}
		$retval [] = $config;
		$retval [] = $css;
		if (count ( self::$_jsToLoad )) {
			$retval [] = 'cgaf.require(' . $json . ',function(){';
		}
		$retval [] = $plugin;
		if (! Request::isAJAXRequest ()) {
			$retval [] = "cgaf.setReady(true);";
		}

		$retval [] = $s;
		if (count ( self::$_plugins )) {
			$retval [] = '});';
		}
		if (count ( self::$_jsToLoad )) {
			$retval [] = '});';
		}
		if (! Request::isAJAXRequest ()) {
			$retval [] = '})();';
			$retval [] = '}';
		}
		\Utils::arrayRemoveEmptyValue ( $retval );

		if (! Request::isAJAXRequest () && count ( $retval ) <= 3 ) {
			return null;
		}
		return JSUtils::renderJSTag ( $retval, false );
	}
}

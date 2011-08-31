<?php
namespace System\Web\JS;
use System\Web\JS\Engine\jQuery;
use System\MVC\Application;
use \System\JSON\JSON;
use \Request;
use \CGAF;
use \Utils;
final class CGAFJS {
	const _JSInstance = 'cgaf';
	private static $_jsToLoad = array();
	private static $_configs = array();
	private static $_css = array();
	private static $_plugins = array();
	private static $_toolbars = array();
	private static $_appOwner;
	private static $_jq;
	public static function initialize(Application $appOwner) {
		static $initialized;
		if ($initialized)
			return;
		if (Request::isAJAXRequest())
		return;
		$initialized = true;
		self::$_appOwner = $appOwner;
		self::$_jq = $jq = new jQuery($appOwner);

		if (!Request::isDataRequest()) {
			$info = $jq->getInfo();
			self::setConfig('appurl',
					Utils::PathToLive($appOwner->getLivePath(null)));
			self::setConfig('asseturl', ASSET_URL);
			self::setConfig('jq.version', $info['version']);
			self::setConfig('jq.compat', $info['compat']);
			self::setConfig('baseurl', BASE_URL);
			self::setConfig('appurl', APP_URL);
		}
		$assets = array(
				'modernizr.js',
				'jquery.js',
				//CGAF_DEBUG ? 'plugins/jquery.lint.js' : '',
				'cgaf/cgaf.js',
				'cgaf/cgaf-jq.js',
				'cgaf/css/cgaf.css');

		if (CGAF_DEBUG) {
			$assets[] = 'cgaf/debug.js';
			$assets[] = 'cgaf/css/debug.css';
		}
		$plugins = CGAF::getConfigs('js.plugins');
		if ($plugins) {
			Utils::arrayMerge($assets, $jq->getAsset($plugins, 'plugins'));
		}
		$plugins = CGAF::getConfigs(
				'js' . self::$_appOwner->getController()->getControllerName()
						. '.plugins');
		if ($plugins) {
			Utils::arrayMerge($assets, $jq->getAsset($plugins, 'plugins'));
		}
		self::$_appOwner->addClientAsset($jq->getAsset($assets));
		$jq->initialize($appOwner);
		return true;
	}
	public static function loadUI() {
		self::$_jq->loadUI();
	}
	private static function getAppOwner() {
		return AppManager::getInstance();
	}
	public static function getJSToLoad() {
		return self::$_jsToLoad;
	}
	public static function setConfig($configName, $value) {
		self::$_configs[$configName] = $value;
		//$arg = func_get_args();
		//self::callMethod('setConfig',$arg);// ->addClientScript ( 'cgaf.setConfig(\'appurl\',\'' . . '\');');
	}
	private static function callMethod($name, $args) {
		$script = self::_JSInstance . '.' . $name . '(';
		$arg = array();
		foreach ($args as $v) {
			$arg[] = JSON::encode($v);
		}
		$script .= implode(',', $arg) . ')';
		self::getAppOwner()->addClientScript($script);
	}
	public static function loadStyleSheet($stylesheet) {
		self::$_css[] = $stylesheet;
	}
	public static function loadScript($js) {
		if (!Utils::isLive($js)) {
			$js = AppManager::getInstance()->getLiveAsset($js);
		}
		if ($js) {
			self::$_jsToLoad[] = $js;
		}
	}
	public static function loadPlugin($plugin,$direct=false) {
		if ($direct) {
			$plugin = self::getPluginURL($plugin);
			self::$_appOwner->addClientAsset($plugin);
			return;

		}
		if (!in_array(self::$_plugins, $plugink)) {
			self::$_plugins[] = $plugin;
		}
	}
	public static function addToolbar($id, $title = null, $action = null) {
		self::$_toolbars[] = array(
				'id' => $id,
				'title' => $title,
				'action' => $action);
	}
	public static function getPluginURL($pluginName) {
		return self::$_jq->getAsset($pluginName.'.js','plugins');
	}
	public static function Render($s) {
		$s = is_array($s) ? implode(';', $s) : $s;
		$s = CGAF_DEBUG ? $s : JSUtils::Pack($s);
		$json = JSON::encode(self::getJSToLoad());
		$config = null;
		if (count(self::$_configs)) {
			$config = self::_JSInstance . '.setConfig('
					. JSON::encode(self::$_configs) . ');';
		}
		$css = count(self::$_css) ? self::_JSInstance . '.loadStyleSheet('
						. JSON::encode(self::$_css) . ');' : null;
		if (count(self::$_plugins)) {
			$plugin = PHP_EOL . self::_JSInstance . '.loadJQPlugin('
					. JSON::encode(self::$_plugins) . ',function(){';
		} else {
			$plugin = null;
		}
		$retval = PHP_EOL
				. '<script type="text/javascript" language="javascript">(function($) { ';
		$retval .= PHP_EOL . 'if (!$) { $=jQuery;}';
		$retval .= PHP_EOL . $config;
		$retval .= PHP_EOL . $css;
		if (count(self::$_jsToLoad)) {
			$retval .= PHP_EOL . 'cgaf.require(' . $json . ',function(){';
		}
		$retval .= $plugin;
		if (!Request::isAJAXRequest()) {
			$retval .= "cgaf.setReady(true);";
		}
		$retval .= PHP_EOL . $s;
		if (count(self::$_plugins)) {
			$retval .= PHP_EOL . '});';
		}
		if (count(self::$_jsToLoad)) {
			$retval .= PHP_EOL . '});';
		}
		$retval .= '})();</script>';
		return $retval;
	}
}

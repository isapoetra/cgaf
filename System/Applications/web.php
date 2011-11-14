<?php
namespace System\Applications;
use System\Web\UI\Items\BreadCrumbItem;
use \CGAF, \Utils, \URLHelper, \Request;
use System\API\PublicApi;
use System\Web\Utils\HTMLUtils;
use System\Exceptions\SystemException;
use System\JSON\JSON;
use System\JSON\JSONResult;
class WebApplication extends AbstractApplication implements \IWebApplication {
	private $_script = '';
	private $_scripts = array();
	private $_directSript = '';
	private $_jsEngine;
	private $_clientScripts = array();
	private $_clientDirectScripts = array();
	private $_metas = array();
	private $_styleSheet = array();
	private $_crumbs = array();
	/**
	 *
	 * Constructor
	 * @param string $appPath
	 * @param string $appName
	 */
	function __construct($appPath, $appName) {
		parent::__construct($appPath, $appName);
	}
	/**
	 *
	 * Clear Client Asset And Client Script
	 */
	public function clearClient() {
		$this->_clientAssets->clear();
		$this->_clientScripts = array();
		$this->_clientDirectScripts = array();
	}

	/**
	 *
	 * Enter description here ...
	 * @param mixed $script
	 */
	function addClientScript($script) {
		if (!$script) {
			return;
		}
		$this->_clientScripts[] = $script;
	}


	function isFromHome() {
		return true;
	}
	function getAppManifest($force = false) {
		$f = CGAF_PATH . 'manifest/' . $this->getAppId() . '.manifest';
		//unlink($f);
		if ($force || !is_file($f)) {
			$manifest = CGAF::getConfig('app.manifest');
			$man = $this->getConfig('app.manifest');
			\Utils::arrayMerge($manifest, $man, false, true);
			$s = 'CACHE MANIFEST' . PHP_EOL;
			$s .= '#generated : ' . time() . PHP_EOL;
			foreach ($manifest as $k => $v) {
				if (!$v)
					continue;
				$s .= PHP_EOL . strtoupper($k) . ':' . PHP_EOL;
				if (is_array($v)) {
					$s .= implode(PHP_EOL, $v) . PHP_EOL;
				} elseif (is_string($v)) {
					$s .= $v . PHP_EOL;
				}
			}
			$s .= '#---EOF---' . PHP_EOL;
			file_put_contents($f, $s);
		}
		return BASE_URL . 'manifest/' . basename($f);
	}
	function addClientDirectScript($script) {
		if (!$script) {
			return;
		}
		$this->_clientDirectScripts[] = $script;
	}
	function getStyleSheet() {
		return $this->_styleSheet;
	}
	function addStyleSheet($s) {
		if (!in_array($s, $this->_styleSheet)) {
			$this->_styleSheet[] = $s;
		}
	}
	function getClientDirectScript() {
		return $this->_clientDirectScripts;
	}
	function getClientScript() {
		return $this->_clientScripts;
	}
	public function getMetaHeader() {
		return $this->_metas;
	}
	public function addMetaHeader($name, $attr = null, $tag = "meta", $overwrite = false) {
		$rattr = array();
		if (is_array($name)) {
			$rattr = $name;
		} elseif (is_string($attr)) {
			if (!$attr) {
				return;
			}
			$rattr["content"] = $attr;
		} elseif (is_array($attr) || is_object($attr)) {
			foreach ($attr as $k => $v) {
				$rattr[$k] = $v;
			}
		}
		$metas = array(
				'tag' => $tag);
		if ($name && is_string($name)) {
			$metas['name'] = $name;
		}
		if ($overwrite) {
			$nmetas = array();
			$name = isset($metas['name']) ? $metas['name'] : (isset($attr['name']) ? $attr['name'] : null);
			foreach ($this->_metas as $k => $meta) {
				if ($metas['tag'] !== $meta['tag'] || @$meta['name'] !== $name) {
					$nmetas[] = $meta;
				}
			}
			$this->_metas = $nmetas;
		}
		$metas['attr'] = $rattr;
		$this->_metas[] = $metas;
	}
	protected function initRun() {
		parent::initRun();
	}
	function getAsset($data, $prefix = null) {
		if (!is_array($data)) {
			//parent::getAsset($data,$prefix);
			$ext = strtolower(Utils::getFileExt($data, false));
			switch ($ext) {
			case 'css':
			case 'js':
				$retval = null;
				if (!$this->isDebugMode()) {
					$retval = parent::getAsset(Utils::changeFileExt($data, 'min.' . $ext), $prefix);
				}
				if (!$retval) {
					$retval = parent::getAsset($data, $prefix);
					if (!$retval && $this->isDebugMode()) {
						$retval = parent::getAsset(Utils::changeFileExt($data, 'min.' . $ext), $prefix);
					}
				}
				return $retval;
			}
		}
		return parent::getAsset($data, $prefix);
	}
	function getAssetPath($data, $prefix = null) {
		if ($data === null) {
			return $this->getAppPath(true) . $this->getConfig('livedatapath', 'assets') . DS;
		}
		$hasdot = strpos($data, ".") !== false;
		$type = $hasdot ? substr($data, strrpos($data, ".") + 1) : '';
		$search = array();
		$rprefix = $prefix;
		$prefix = $prefix ? $prefix : Utils::getFileExt($data, false);
		$tpath = null;
		if (!isset($this->_assetCache[$type][$prefix])) {
			if ($rprefix) {
				$search[] = $rprefix;
				$search[] = $rprefix . DS . $type;
			}
			$search[] = $type;
			$add = null;
			$def = "";
			$add = null;
			switch (strtolower($type)) {
			case "js":
				$def = "js";
				break;
			case "css":
			case "gif":
			case "jpg":
			case "png":
			case "jpeg":
			case "ico":
				$def = 'images';
				if ($type == "ico") {
					$search[] = 'images';
					$def = "icon";
				}
				$ctheme = $this->getConfig("themes", "default");
				$search[] = "themes" . DS . $ctheme . DS . $rprefix . DS . $def;
				if ($type != 'css') {
					$search[] = "themes" . DS . $ctheme . DS . $rprefix . DS . $def;
				}
				$search[] = "themes" . DS . $ctheme . DS . $def;
				$search[] = $def;
				break;
			}
			//$search = array_merge($search, array($prefix . DS . $type));
			if ($prefix !== $type) {
				$search[] = $type . DS . $prefix;
			}
			$search[] = '';
			$ap = $this->getConfig('livedatapath', 'assets');
			$retval = array();
			$spath = array(
					$this->getLivePath(false),
					$this->getAppPath(),
					CGAF_SHARED_PATH,
					SITE_PATH);
			foreach ($spath as $v) {
				foreach ($search as $value) {
					$retval[] = Utils::ToDirectory($v . $ap . DS . $value . DS);
				}
			}
			$this->_assetCache[$type][$prefix] = $retval;
			//ppd($retval);
		}
		return $this->_assetCache[$type][$prefix];
	}
	public function renderClientAsset($mode = null) {
		$retval = '';
		$retval = $this->getClientAsset()->render(true, $mode);
		return $retval;
	}
	function getAppUrl() {
		static $url;
		if (!$url) {
			$params = array(
					'__appId' => $this->getAppId());
			if (\Request::isMobile()) {
				$params['__mobile'] = 1;
			}
			$def = CGAF::getConfig('defaultAppId') === $this->getAppId() ? BASE_URL : URLHelper::addParam(BASE_URL, $params);
			$url = $this->getConfig('app.url', $def);
		}
		return $url;
	}
	function Initialize() {
		if (parent::Initialize()) {
			if (!defined('APP_URL')) {
				define('APP_URL', $this->getAppUrl());
			}
			CGAF::addClassPath('System', $this->getAppPath() . DS . 'classes' . DS);
			CGAF::addAlowedLiveAssetPath($this->getLivePath());
			CGAF::addAlowedLiveAssetPath($this->getAppPath() . $this->getConfig('livedatapath', 'assets'));
			return true;
		}
		return false;
	}
	public function getAgentSuffix() {
		return Utils::getAgentSuffix();
	}
	/**
	 *
	 * Enter description here ...
	 * @param String $assetName
	 */
	public function getAssetAgent($assetName) {
		$fname = Utils::getFileName($assetName);
		$assetAgent = Utils::changeFileName($assetName, $fname . $this->getAgentSuffix());
		return $this->getAsset($assetAgent);
	}
	/**
	 *
	 * Enter description here ...
	 * @return /System/Web/JS/Engine/IJSEngine
	 */
	public function getJSEngine() {
		if (!$this->_jsEngine) {
			$c = 'System\\Web\JS\Engine\\' . $this->getConfig('app.jsengine', 'jQuery');
			$this->_jsEngine = new $c($this);
			$this->_jsEngine->initialize($this);
		}
		return $this->_jsEngine;
	}
	protected function getDefaultTemplateParam() {
		return array(
				"baseurl" => BASE_URL,
				"imageLogo" => $this->getLiveData("logo.pg"));
	}
	function parseScript($m) {
		$match = array();
		preg_match_all('|(\w+\s*)=(\s*".*?")|', $m[0], $match);
		if (!$match[0]) {
			return $m[0];
		}
		if (!empty($m[3])) {
			if (!in_array('ignore', $match[1])) {
				$this->_script .= $m[3];
			} else {
				$this->_directSript .= $m[3];
			}
			return '';
		}
		$val = array();
		foreach ($match[1] as $k => $v) {
			$s = $match[2][$k];
			$s = substr($s, 1, strlen($s) - 2);
			$val[$v] = $s;
		}
		foreach ($this->_scripts as $v) {
			if (!isset($val['src']))
				continue;
			if ($v['src'] === $val['src']) {
				return;
			}
		}
		if ($val) {
			$this->_scripts[] = $val;
		}
		return;
	}
	protected function checkInstall() {
	}
	protected function prepareOutputData($s) {
		//ppd($s);
		if (Request::isJSONRequest()) {
			header("Content-Type: application/json, text/javascript;charset=UTF-8", true);
			if (!is_string($s)) {
				if (is_object($s) && $s instanceof \IRenderable) {
					$s = $s->Render(true);
				}
				if (!is_string($s)) {
					return JSON::encode($s);
				}
			}
			return $s;
		} elseif (Request::isXMLRequest()) {
			header("Content-Type: application/xml;charset=UTF-8", true);
			if (!is_string($s)) {
				ppd($s);
			}
			return trim($s);
		} else {
			$format = Request::get('__data');
			switch (strtolower($format)) {
			case 'html':
			case 'text':
			case 'html':
				return \Utils::toString($s);
				break;
			}
		}
		throw new SystemException('Unhandled Request Data Format');
	}
	function prepareOutput($s) {
		if (Request::isDataRequest()) {
			return $this->prepareOutputData($s);
		}
		$c = 0;
		$stemp = $s;
		if ($this->getConfig("output.prepare", false)) {
			//return $stemp;
			//using('libs.minifier.minify.min.lib.Minify.HTML');
			return preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "", $stemp);
		}
		return $stemp;
	}
	public function getCrumbs() {
		return $this->_crumbs;
	}
	/**
	 *
	 * Enter description here ...
	 * @param array $arrCrumbs
	 */
	public function addCrumbs($arrCrumbs) {
		foreach ($arrCrumbs as $c) {
			$this->addCrumb($c);
		}
	}
	/**
	 *
	 * Enter description here ...
	 * @param mixed $crumb
	 */
	public function addCrumb($crumb) {
		$item = new BreadCrumbItem();
		$item->bind($crumb);
		$this->_crumbs[] = $item;
	}
	public function clearCrumbs() {
		$this->_crumbs = array();
	}
	public function handleCommetRequest() {
		Response::write("halooo");
		return true;
	}
	protected function initRequest() {
		if (!$this->isDebugMode() && !\Request::isAJAXRequest() && !\Request::isDataRequest()) {
			$gaid = $this->getConfig('google.analytics.account');
			if ($gaid) {
				$script = <<<EOT
var _gaq = _gaq || [];
_gaq.push(['_setAccount', '$gaid']);
_gaq.push(['_trackPageview']);
(function() {
	var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
	ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
	var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
})();
EOT;
				$this->addClientDirectScript($script);
			}
		}
		header_remove('X-Powered-By');
		header_remove('Server');
		//$script = 'window.external.AddSearchProvider("'.BASE_URL.'search/osd/?r=def");';
		//$this->addClientDirectScript($script);
		$this->addMetaHeader('charset', 'utf-8');
		$this->addMetaHeader(array(
						'http-equiv' => 'Content-Type',
						'content' => 'text/html; charset=UTF-8'));
		$info = $this->getAppInfo();
		if ($fav = $this->getLiveAsset("favicon.png")) {
			$this->addMetaHeader(null, array(
							'rel' => "shortcut icon",
							'href' => $fav), 'link');
		}
		$this->addMetaHeader(null, array(
						'rel' => "search",
						'type' => "application/opensearchdescription+xml",
						'title' => 'CGAF Search',
						'href' => BASE_URL . 'search/opensearch/?r=def'), 'link');
		$this->addMetaHeader('author', 'Iwan Sapoetra');
		$this->addMetaHeader('copyright', date('M Y'));
		$descr = $this->getConfig('app.description', $info->app_descr ? $info->app_descr : CGAF::getConfig('cgaf.description', 'CGAF'));
		$this->addMetaHeader('description', $descr);
		$this->addMetaHeader('keywords', array(
						'content' => $this->getConfig('app.keywords', CGAF::getConfig('cgaf.keywords', 'CGAF'))));
		$this->addMetaHeader('Version', $info->app_version);
		$this->addClientAsset(strtolower($this->getAppName()) . ".css");
	}
	protected function renderHeader() {
		return "<head>";
	}
	function renderMetaHead() {
		$retval = '';
		foreach ($this->_metas as $value) {
			$retval .= '<' . $value['tag'] . ' ' . (isset($value['name']) ? ' name="' . $value['name'] . '" ' : ' ');
			$retval .= HTMLUtils::renderAttr($value['attr']);
			$retval .= '>';
		}
		return $retval;
	}
	function isDebugMode() {
		static $d;
		if ($d === null) {
			$d = $this->getConfig('app.debugmode', \CGAF::isDebugMode());
			if ($d === true) {
				if (!CGAF::isDebugMode()) {
					$r = $this->getConfig('app.allowedebughost');
					if ($r) {
						$r = explode(',', $r);
						$d = in_array($_SERVER['REMOTE_ADDR'], $r);
					} else {
						$d = false;
					}
				}
			}
		}
		return $d;
	}
	public function Run() {
		$a = Request::get("__url");
		$a = explode("/", $a);
		if ($a[0] == "_appList") {
			Response::StartBuffer();
			include Utils::ToDirectory($this->getSharedPath() . "Views/applist.php");
			return Response::EndBuffer(false);
		}
		return false;
	}
}
?>

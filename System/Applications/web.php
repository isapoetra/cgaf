<?php
namespace System\Applications;
use \CGAF, \Utils, \URLHelper, \Request;
use System\API\PublicApi;
use System\Web\Utils\HTMLUtils;
use System\Exceptions\SystemException;
use System\JSON\JSON;
class WebApplication extends AbstractApplication implements \IWebApplication {
	private $_script = '';
	private $_scripts = array();
	private $_directSript = '';
	private $_jsEngine;
	private $_clientScripts = array();
	private $_metas = array();
	private $_styleSheet = array();
	function __construct($appPath, $appName) {
		parent::__construct($appPath, $appName);
	}
	protected function clearClient() {
		$this->_clientAssets->clear();
		$this->_clientScripts = array();
	}
	function addClientScript($script) {
		if (!$script) {
			return;
		}
		$this->_clientScripts[] = $script;
	}
	function getStyleSheet() {
		return $this->_styleSheet;
	}
	function addStyleSheet($s) {
		if (!in_array($s, $this->_styleSheet)) {
			$this->_styleSheet[] = $s;
		}
	}
	function getClientScript() {
		return $this->_clientScripts;
	}
	public function getMetaHeader() {
		return $this->_metas;
	}
	public function addMetaHeader($name, $attr = null, $tag = "meta") {
		$rattr = array();
		if (is_array($name)) {
			$rattr = $name;
		} elseif (is_string($attr)) {
			$rattr["content"] = $attr;
		} elseif (is_array($attr) || is_object($attr)) {
			foreach ($attr as $k => $v) {
				$rattr[$k] = $v;
			}
		}
		$metas = array('tag' => $tag);
		if (is_string($name)) {
			$metas['name'] = $name;
		}
		$metas['attr'] = $rattr;
		$this->_metas[] = $metas;
	}
	protected function initRun() {
		parent::initRun();
		if (!Request::isAJAXRequest()) {
			PublicApi::Initialize($this);
			$this
					->addMetaHeader(
							array('http-equiv' => 'Content-Type', 'content' => 'text/html'));
			$this
					->addMetaHeader('description',
							$this
									->getConfig('app.description',
											CGAF::getConfig(
													'cgaf.description', 'CGAF')));
			$this->addMetaHeader('Version', CGAF_VERSION);
			$this
					->addMetaHeader('Application Version',
							$this->getConfig('app.version', CGAF_VERSION));
			$this->addClientAsset(strtolower($this->getAppName()) . ".css");
		}
	}
	function getAsset($data, $prefix = null) {
		if (!is_array($data)) {
			//parent::getAsset($data,$prefix);
			$ext = strtolower(Utils::getFileExt($data, false));
			switch ($ext) {
			case 'css':
			case 'js':
				$retval = parent::getAsset($data, $prefix);
				if (!$retval) {
					$retval = parent::getAsset(
							Utils::changeFileExt($data, 'min.' . $ext),
							$prefix);
				}
				return $retval;
			}
		}
		return parent::getAsset($data, $prefix);
	}
	function getAssetPath($data, $prefix = null) {
		if ($data === null) {
			return $this->getAppPath(true)
					. $this->getConfig('livedatapath', 'assets') . DS;
		}
		$hasdot = strpos($data, ".") !== false;
		$type = $hasdot ? substr($data, strrpos($data, ".") + 1) : '';
		$search = array();
		$prefix = $prefix ? $prefix : Utils::getFileExt($data, false);
		$tpath = null;
		if (!isset($this->_assetCache[$type][$prefix])) {
			$add = null;
			$def = "";
			$add = null;
			switch (strtolower($type)) {
			case "css":
				$def = "css";
				break;
			case "js":
				$def = "js";
				break;
			case "gif":
			case "jpg":
			case "png":
			case "jpeg":
			case "ico":
				if ($type == "ico") {
					$def = "icon";
				}
				$prefix = $prefix ? $prefix : 'images';
				$type = "images";
				$tpath = "themes" . DS . $this->getConfig("themes", "default");
				break;
			}
			if ($tpath) {
				$search = array_merge($search,
						array($tpath . DS . $type, $tpath));
			}
			$search = array_merge($search, array($type));
			if ($prefix !== $type) {
				$search[] = $type . DS . $prefix;
			}
			$ap = $this->getConfig('livedatapath', 'assets');
			$retval = array();
			$assetpath = $this->getLivePath(false);
			if (CGAF_DEBUG) {
				foreach ($search as $value) {
					$retval[] = $this->getDevPath($value);
				}
				foreach ($search as $value) {
					$retval[] = CGAF_DEV_PATH . "$ap/$value/";
				}
				$retval[] = CGAF_DEV_PATH . $ap;
			}
			$spath = array($this->getAppPath(), CGAF_CORE_PATH, SITE_PATH);
			foreach ($spath as $v) {
				foreach ($search as $value) {
					//if $retval[] = $prefix;
					$retval[] = $v . $ap . DS . $value . DS;
					$retval[] = $v . $ap . DS;
				}
			}
			$this->_assetCache[$type][$prefix] = $retval;
		}
		return $this->_assetCache[$type][$prefix];
	}
	public function renderClientAsset($mode = null) {
		$retval = '';

		$retval = $this->getClientAsset()->render(true, $mode);//AssetHelper::renderAsset($assets);
		return $retval;
	}
	function getAppUrl() {
		static $url;
		if (!$url) {
			$url = $this
					->getConfig('app.url',
							URLHelper::addParam(BASE_URL,
									array('__appId' => $this->getAppId())));
		}
		return $url;
	}
	function Initialize() {
		if (parent::Initialize()) {
			if (!defined('APP_URL')) {
				define('APP_URL', $this->getAppUrl());
			}
			CGAF::addClassPath('System',
					$this->getAppPath() . DS . 'classes' . DS);
			//CGAF::addNamespaceSearchPath("System", $this->getAppPath ().'/classes/');
			//CGAF::addNamespaceSearchPath("System", $this->getAppPath ().'/classes/System/');
			//CGAF::addNamespaceSearchPath($this->getAppName (), $this->getAppPath () );
			CGAF::addAlowedLiveAssetPath($this->getLivePath());
			CGAF::addAlowedLiveAssetPath(
					$this->getAppPath()
							. $this->getConfig('livedatapath', 'assets'));
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
		$assetAgent = Utils::changeFileName($assetName,
				$fname . $this->getAgentSuffix());
		return $this->getAsset($assetAgent);
	}
	public function getJSEngine() {
		if (!$this->_jsEngine) {
			$c = 'System\\Web\JS\Engine\\'
					. $this->getConfig('app.jsengine', 'jQuery');
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
		if (Request::isJSONRequest()) {
			header(
					"Content-Type: application/json, text/javascript;charset=UTF-8",
					true);
			if (!is_string($s)) {
				if (is_object($s) && $s instanceof JSONResult) {
					return $s->Render(true);
				}
				return JSON::encode($s);
			}
			return $s;
		} elseif (Request::isXMLRequest()) {
			header("Content-Type: application/xml;charset=UTF-8", true);
			if (!is_string($s)) {
				ppd($s);
			}
			return trim($s);
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
			return preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "",
					$stemp);
		}
		return $stemp;
	}
	public function handleCommetRequest() {
		Response::write("halooo");
		return true;
	}
	protected function initRequest() {
		return true;
	}
	protected function renderHeader() {
		return "<head>";
	}
	function renderMetaHead() {
		$retval = '';
		foreach ($this->_metas as $value) {
			$retval .= '<' . $value['tag'] . ' '
					. (isset($value['name']) ? ' name="' . $value['name']
									. '" ' : ' ');
			$retval .= HTMLUtils::renderAttr($value['attr']);
			$retval .= '/>';
		}
		return $retval;
	}
	public function Run() {
		$a = Request::get("__url");
		$a = explode("/", $a);
		if ($a[0] == "_appList") {
			Response::StartBuffer();
			include Utils::ToDirectory(
					$this->getSharedPath() . "Views/applist.php");
			return Response::EndBuffer(false);
		}
		return false;
	}
}
?>

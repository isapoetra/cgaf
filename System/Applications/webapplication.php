<?php
namespace System\Applications;
use \System\Web\JS\CGAFJS;
use \System\Session\Session;
use \Logger;
use \System\Web\WebUtils;
use System\ACL\ACLHelper;
use System\Web\UI\Items\BreadCrumbItem;
use CGAF, Utils, URLHelper, Request;
use System\API\PublicApi;
use System\Web\Utils\HTMLUtils;
use System\Exceptions\SystemException;
use System\JSON\JSON;
use System\JSON\JSONResult;
use \System\MVC\Application;
/**
 *
 */
class WebApplication extends Application implements IWebApplication {
	private $_script = '';
	private $_scripts = array ();
	private $_directSript = '';
 /**
  * @var \IJSEngine
  */
	private $_jsEngine;
	private $_clientScripts = array ();
	private $_clientDirectScripts = array ();
	private $_metas = array ();
	private $_styleSheet = array ();
	private $_crumbs = array ();
	private $_appURL;
	//private $_isDebugMode;
	//private $_assetCached = array ();
	/**
	 * Constructor
	 *
	 * @param $appPath string
	 * @param $appName string
	 */
	function __construct($appPath, $appName) {
    if (!\System::isWebContext()) {
      throw new SystemException('please run from web');
    }
		parent::__construct ( $appPath, $appName );
	}
	/**
	 * Clear Client Asset And Client Script
	 */
	public function clearClient() {
		/*
		 * static $first; if ($first === null) { $first = true; }
		 */
		$this->_clientAssets->clear ();
		$this->_clientScripts = array ();
		$this->_clientDirectScripts = array ();
		/*
		 * if (!$first) { ppd('clear'); } $first = false;
		 */
	}
	/**
	 * Enter description here .
	 * ..
	 *
	 * @param $script mixed
	 */
	function addClientScript($script) {
		if (! $script) {
			return;
		}
		$this->_clientScripts [] = $script;
	}
	function isFromHome() {
		return true;
	}
	function getAppManifest($force = false) {
		$f = CGAF_PATH . 'manifest/' . $this->getAppId () . '.manifest';
		// unlink($f);
		if ($force || ! is_file ( $f )) {
			$man = $this->getConfig ( 'app.manifest' );
			Utils::generateManifest ( $man, $this->getAppId () );
		}
		if (! $this->getConfig ( 'site.enableoffline' )) {
			return false;
		}
		return BASE_URL . 'manifest/' . basename ( $f );
	}
	function addClientDirectScript($script) {
		if (! $script) {
			return;
		}
		$this->_clientDirectScripts [] = $script;
	}
	function getStyleSheet() {
		return $this->_styleSheet;
	}
	function addStyleSheet($s) {
		if (! in_array ( $s, $this->_styleSheet )) {
			$this->_styleSheet [] = $s;
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
		$rattr = array ();
		if (is_array ( $name )) {
			$rattr = $name;
		} elseif (is_string ( $attr )) {
			if (! $attr) {
				return;
			}
			$rattr ["content"] = $attr;
		} elseif (is_array ( $attr ) || is_object ( $attr )) {
			foreach ( $attr as $k => $v ) {
				$rattr [$k] = $v;
			}
		}
		$metas = array (
				'tag' => $tag
		);
		if ($name && is_string ( $name )) {
			$metas ['name'] = $name;
		}
		if ($overwrite) {
			$nmetas = array ();
			$name = isset ( $metas ['name'] ) ? $metas ['name'] : (isset ( $attr ['name'] ) ? $attr ['name'] : null);
			foreach ( $this->_metas as  $meta ) {
				if ($metas ['tag'] !== $meta ['tag'] || @$meta ['name'] !== $name) {
					$nmetas [] = $meta;
				}
			}
			$this->_metas = $nmetas;
		}
		$metas ['attr'] = $rattr;
		$this->_metas [] = $metas;
	}
	protected function initRun() {
		parent::initRun ();
    if (Request::get('__generateManifest') == '1') {
      $this->getAppManifest(true);
    }
    if (Request::get("__init")) {
      Session::set("hasinit", true);
      $mode = Request::get("__js") == "true" ? true : false;
      Session::set("__jsmode", $mode);
    }
	}
	function getAsset($data, $prefix = null) {

		if (! is_array ( $data )) {
			$ext = strtolower ( Utils::getFileExt ( $data, false ) );
			switch ($ext) {
				case 'css' :
				case 'js' :
					$min = parent::getAsset ( Utils::changeFileExt ( $data, 'min.' . $ext ), $prefix );
					//ppd(Utils::changeFileExt ( $data, 'min.' . $ext ));
					if ($min && ! $this->isDebugMode ()) {
						return $min;
					}				
					$retval = parent::getAsset ( $data, $prefix );					
					return $retval ? $retval : $min;
			}
		}
		return parent::getAsset ( $data, $prefix );
	}

	public function renderClientAsset($mode = null) {
		//$retval = '';
		$retval = $this->getClientAsset ()->render ( true, $mode );
		return $retval;
	}
	function getAppUrl() {
		if (! $this->_appURL) {
			$params = array (
					'__appId' => $this->getAppId ()
			);
			if (\Request::isMobile ()) {
				$params ['__mobile'] = 1;
			}
			$def = CGAF::getConfig ( 'defaultAppId' ) === $this->getAppId () ? BASE_URL : URLHelper::addParam ( BASE_URL, $params );
			$this->_appURL = $this->getConfig ( 'app.url', $def );
		}
		return $this->_appURL;
	}
	function Initialize() {
		if (parent::Initialize ()) {
			if (! defined ( 'APP_URL' )) {
				define ( 'APP_URL', $this->getAppUrl () );
			}
			CGAF::addClassPath ( 'System', $this->getAppPath () . DS . 'classes' . DS );
			CGAF::addAlowedLiveAssetPath ( $this->getLivePath () );
			CGAF::addAlowedLiveAssetPath ( $this->getAppPath () . $this->getConfig ( 'livedatapath', 'assets' ) );
			return true;
		}
		return false;
	}

	public function getAgentSuffix() {
		return Utils::getAgentSuffix ();
	}
/**
 * @param $assetName
 * @return array|mixed|null
 */
	public function getAssetAgent($assetName) {
		$fname = Utils::getFileName ( $assetName );
		$assetAgent = Utils::changeFileName ( $assetName, $fname . $this->getAgentSuffix () );
		return $this->getAsset ( $assetAgent );
	}
  function Authenticate() {
    if ($this->getConfig('auth.usecaptcha', false)) {
      if (!$this->isValidCaptcha("__captcha", true)) {
        throw new SystemException ('error.invalidcaptcha');
      }
    }
    return parent::Authenticate();
  }
  protected function isValidCaptcha($c,$a) {

  }
	/**
   * @return \IJSEngine
   */
	public function getJSEngine() {
		if (! $this->_jsEngine) {
			$c = 'System\\Web\JS\Engine\\' . $this->getConfig ( 'js.engine', 'jQuery' );
			$this->_jsEngine = new $c ( $this );
			$this->_jsEngine->initialize ( $this );
		}
		return $this->_jsEngine;
	}
  /**
   * @return array
   * @deprecated
   */
	protected function getDefaultTemplateParam() {
		return array (
				"baseurl" => BASE_URL,
				"imageLogo" => $this->getLiveAsset( "logo.pg" )
		);
	}
	function parseScript($m) {
		$match = array ();
		preg_match_all ( '|(\w+\s*)=(\s*".*?")|', $m [0], $match );
		if (! $match [0]) {
			return $m [0];
		}
		if (! empty ( $m [3] )) {
			if (! in_array ( 'ignore', $match [1] )) {
				$this->_script .= $m [3];
			} else {
				$this->_directSript .= $m [3];
			}
			return '';
		}
		$val = array ();
		foreach ( $match [1] as $k => $v ) {
			$s = $match [2] [$k];
			$s = substr ( $s, 1, strlen ( $s ) - 2 );
			$val [$v] = $s;
		}
		foreach ( $this->_scripts as $v ) {
			if (! isset ( $val ['src'] ))
				continue;
			if ($v ['src'] === $val ['src']) {
				return true;
			}
		}
		if ($val) {
			$this->_scripts [] = $val;
		}
		return true;
	}
	protected function checkInstall() {
	}
	protected function prepareOutputData($s) {
		if (Request::isJSONRequest ()) {
			header ( "Content-Type: application/json, text/javascript;charset=UTF-8", true );
			if (! is_string ( $s )) {
				if (is_object ( $s ) && $s instanceof \IRenderable) {
          /** @noinspection PhpUndefinedMethodInspection */
          $s = $s->Render ( true );
				}
				if (! is_string ( $s )) {
					return JSON::encode ( $s );
				}
			}
			return $s;
		} elseif (Request::isXMLRequest ()) {
			header ( "Content-Type: application/xml;charset=UTF-8", true );
			if (! is_string ( $s )) {
				ppd ( $s );
			}
			return trim ( $s );
		} else {
			$format = Request::get ( '__data' );
			switch (strtolower ( $format )) {
				case 'html' :
				case 'text' :
				case 'html' :
				case '' :
					return \Convert::toString ( $s );
					break;
			}
		}
		throw new SystemException ( 'Unhandled Request Data Format' );
	}
	function prepareOutput($s) {
		if (Request::isDataRequest ()) {
			return $this->prepareOutputData ( $s );
		}
		//$c = 0;
		$stemp = $s;
		if ($this->getConfig ( "output.prepare", false )) {
			// return $stemp;
			// using('libs.minifier.minify.min.lib.Minify.HTML');
			// return preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "",
			// $stemp);
		}
		return $stemp;
	}
	public function getCrumbs() {
		return $this->_crumbs;
	}
	/**
	 * Enter description here .
	 * ..
	 *
	 * @param $arrCrumbs array
	 */
	public function addCrumbs($arrCrumbs) {
		foreach ( $arrCrumbs as $c ) {
			$this->addCrumb ( $c );
		}
	}
	/**
	 * Enter description here .
	 * ..
	 *
	 * @param $crumb mixed
	 */
	public function addCrumb($crumb) {
		$item = new BreadCrumbItem ();
		$item->bind ( $crumb );
		$this->_crumbs [] = $item;
	}
	public function clearCrumbs() {
		$this->_crumbs = array ();
	}
  protected function handleRequest() {
    $retval = parent::handleRequest();
    if (is_string($retval) && (Request::isDataRequest() || Request::isAJAXRequest())) {
      $retval .= CGAFJS::Render($this->getClientScript());
    }
    return $retval;
  }
	public function handleCommetRequest() {
		\Response::write ( "halooo" );
		return true;
	}
	protected function initRequest() {
		if (! $this->isDebugMode () && ! \Request::isAJAXRequest () && ! \Request::isDataRequest ()) {
			$gaid = $this->getConfig ( 'google.analytics.account' );
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
				$this->addClientDirectScript ( $script );
			}
		}
		header_remove ( 'X-Powered-By' );
		header_remove ( 'Server' );
		// $script =
		// 'window.external.AddSearchProvider("'.BASE_URL.'search/osd/?r=def");';
		// $this->addClientDirectScript($script);
		$this->addMetaHeader ( 'charset', 'utf-8' );
		$this->addMetaHeader ( array (
				'http-equiv' => 'Content-Type',
				'content' => 'text/html; charset=UTF-8'
		) );
		if (! \Request::isDataRequest ()) {
			$info = $this->getAppInfo ();
			$fav = $this->getLiveAsset ( "favicon.png" );
			if ($fav) {
				$this->addMetaHeader ( null, array (
						'rel' => "shortcut icon",
						'href' => $fav
				), 'link' );
			}
			$this->addMetaHeader ( null, array (
					'rel' => "search",
					'type' => "application/opensearchdescription+xml",
					'title' => 'CGAF Search',
					'href' => BASE_URL . 'search/opensearch/?r=def'
			), 'link' );
			$this->addMetaHeader ( 'author', 'Iwan Sapoetra' );
			$this->addMetaHeader ( 'copyright', date ( 'M Y' ) );
			$descr = $this->getConfig ( 'app.description', $info->app_descr ? $info->app_descr : CGAF::getConfig ( 'cgaf.description', 'CGAF' ) );
			$this->addMetaHeader ( 'description', $descr );
			$this->addMetaHeader ( 'keywords', array (
					'content' => $this->getConfig ( 'app.keywords', CGAF::getConfig ( 'cgaf.keywords', 'CGAF' ) )
			) );
			$this->addMetaHeader ( 'Version', $info->app_version );
			$metas = $this->getConfigs ( 'site.metas', array (
					array (
							'name' => 'google',
							'value' => 'notranslate'
					)
			) );
			foreach ( $metas as $value ) {
				$this->addMetaHeader ( $value );
			}
      $_crumbs = array();
      $route = $this->getRoute();
      if ($route ['_c'] !== 'home') {
        $_crumbs [] = array(
          'url' => APP_URL,
          'title' => ucwords(__('home')),
          'class' => 'home'
        );
      }
      if ($route ['_c'] !== 'home') {
        $_crumbs [] = array(
          'title' => __('app.route.' . $route ['_c'] . '.title', ucwords($route ['_c'])),
          'url' => URLHelper::add(APP_URL, $route ['_c'])
        );
      }
      if ($route ['_a'] !== 'index') {
        $_crumbs [] = array(
          'title' => ucwords(__($route ['_c'] . '.' . $route ['_c'], $route ['_a'])),
          'url' => URLHelper::add(APP_URL, $route ['_c'] . '/' . $route ['_a'])
        );
      }
      // Session::set('app.isfromhome', false);
      if ($route ['_c'] === 'home') {
        Session::set('app.isfromhome', true);
      }

      if (!\Request::isDataRequest()) {
        CGAFJS::initialize($this);
      }

      $this->addCrumbs($_crumbs);
		}

    parent::initRequest();
	}
	protected function initAsset() {
		$route = $this->getRoute ( );
		if (! Request::isDataRequest ()) {
			if ($route ['_c'] === 'asset' && $route ['_a'] === 'get')
				return;
			if ($this->getConfig ( 'js.bootstrap.enabled', true )) {
				$this->addClientAsset ( 'bootstrap/css/bootstrap.css' );
				$this->addClientAsset ( 'bootstrap/css/bootstrap-responsive.css' );
				$this->addClientAsset ( 'cgaf/css/cgaf-bootstrap.css' );
				$this->addClientAsset ( 'cgaf/css/cgaf-bootstrap-responsive.css' );
				$this->addClientAsset ( 'bootstrap/js/bootstrap.js' );
				$plugins = $this->getConfigs ( 'js.bootstrap.plugins', array () );
				foreach ( $plugins as $p ) {
					$this->addClientAsset ( 'bootstrap/js/' . $p );
				}
			}
			$pref = 'js.' . $this->getConfig ( 'js.engine', 'jQuery' ) . '.plugins';
			$plugins = $this->getConfigs ( $pref );
			if ($plugins) {
				$pref = str_replace ( '.', DS, $pref );
				foreach ( $plugins as $k => $v ) {
					$plugins [$k] = $pref . DS . $v;
				}
				$this->addClientAsset ( $plugins );
			}
      $maset = $this->getConfig('app.mainasset',$this->getAppPath(false));
			$this->addClientAsset ( $maset.'.js' );
			$this->addClientAsset ( $maset.'.css' );

			$this->addClientAsset ( $route['_c'] . '.css' );
			$this->addClientAsset ( $route['_c'] . '.css' );
		}
	}

	function renderMetaHead() {
		$retval = '';
		foreach ( $this->_metas as $value ) {
			$retval .= '<' . $value ['tag'] . ' ' . (isset ( $value ['name'] ) ? ' name="' . $value ['name'] . '" ' : ' ');
			$retval .= HTMLUtils::renderAttr ( $value ['attr'] );
			$retval .= '/>';
		}
		return $retval;
	}

	/*public function Run() {
    parent::Run();
		$a = Request::get ( "__url" );
		$a = explode ( "/", $a );
		if ($a [0] == "_appList") {
			\Response::StartBuffer ();
			include Utils::ToDirectory ( $this->getSharedPath () . "Views/applist.php" );
			return \Response::EndBuffer ( false );
		}
		return false;
	}*/
  protected function cacheCSS($css, $target, $force = false) {
    if (!$target && is_string($css)) {
      $fname = Utils::getFileName($css);
      $target = Utils::changeFileName($css, $fname . $this->getAgentSuffix());
    }
    $fname = $this->getCacheManager()->get($target, 'css');
    // pp($fname);
    if (!$fname || $force) {
      if ($fname && is_file($fname)) {
        unlink($fname);
      }
      $parsed = array();
      if (is_array($css)) {
        foreach ($css as $v) {
          $parsed [] = $this->getAsset($v ['url']);
          $ta = $this->getAssetAgent($v ['url']);
          if ($ta) {
            $parsed [] = $ta;
          }
        }
      } else {
        $tcss = $this->getAsset($css);
        if ($tcss) {
          $parsed [] = $tcss;
        }
        $tcss = $this->getAssetAgent($css);
        if ($tcss) {
          $parsed [] = $tcss;
        }
      }
      if (count($parsed)) {
        $content = WebUtils::parseCSS($parsed, $fname, $this->isDebugMode() == false);
        $fname = $this->getCacheManager()->putString($content, $target, 'css');
      }
    }
    if ($fname) {
      return $this->getLiveAsset($fname);
    }
    return null;
  }
  function assetToLive($asset, $sessionBased = false) {
    if (is_array($asset)) {
      $retval = array();
      foreach ($asset as $ff) {
        if (!$ff)
          continue;
        $file = $this->assetToLive($ff, $sessionBased);
        if ($file) {
          if (!in_array($file, $retval)) {
            $retval [] = $file;
          }
        } elseif ($this->isDebugMode()) {
          Logger::Warning($ff);
        }
      }
      return $retval;
    }
    if (strpos($asset, '://') !== false) {
      return $asset;
    }
    if (!$this->isAllowToLive($asset)) {
      return null;
    }
    $asset = Utils::toDirectory($asset);
    if (!is_file($asset)) {
      return null;
    }
    $ext = Utils::getFileExt($asset, FALSE);
    switch ($ext) {
      case 'assets' :
        return $this->assetToLive($asset);
        break;
      default :
        ;
        break;
    }
    $apath = \Utils::ToDirectory($this->getAppPath() . $this->getConfig('livedatapath', 'assets') . '/');
    if (\Strings::BeginWith($asset, $apath)) {
      $asset = \Strings::Replace($apath, '', $asset);
      return URLHelper::add($this->getAppUrl(), 'asset/get', array(
                                                                  'q' => $asset
                                                             ));
    }
    return CGAF::assetToLive($asset);
  }
  function Run() {
    $retval = parent::Run();
    return $this->prepareOutput($retval);
  }
}
?>

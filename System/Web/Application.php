<?php
if (! defined ( "CGAF" ))
die ( "Restricted Access" );

class WebApplication extends Application implements IWebApplication {
	private $_script = '';
	private $_scripts = array ();
	private $_directSript = '';
	private $_jsEngine;
	private $_clientScripts = array();
	private $_metas =array();
	function __construct($appPath, $appName) {
		parent::__construct($appPath, $appName);
	}

	protected function clearClient() {
		$this->_clientAssets->clear();
		$this->_clientScripts = array();
	}
	function addClientScript($script) {
		$this->_clientScripts[]= $script;
	}
	function getClientScript() {
		return $this->_clientScripts;
	}
	public function getMetaHeader() {
		return $this->_metas;
	}
	public function addMetaHeader($name,$attr=null,$tag="meta") {

		$rattr = array();
		if (is_array($name)) {
			$rattr =  $name;
		}elseif (is_string($attr)) {
			$rattr["content"] =$attr;
		}elseif (is_array($attr) || is_object($attr)) {
			foreach ($array_expression as $k=>$v) {
				$rattr[$k] = $v;
			}
		}
		$metas = array(
		'tag'=>$tag);
		if (is_string($name)) {
			$metas['name'] =$name;
		}
		$metas['attr']=$rattr;
		$this->_metas[] = $metas;
	}
	protected function initRun() {
		parent::initRun ();
		if (!Request::isAJAXRequest()) {
			$this->addMetaHeader(array('http-equiv'=>'Content-Type','content'=>'text/html'));
			$this->addMetaHeader('description',$this->getConfig('app.description',CGAF::getConfig('cgaf.description','CGAF')));
			$this->addMetaHeader('Version',CGAF_VERSION);
			$this->addMetaHeader('Application Version', $this->getConfig('app.version',CGAF_VERSION));
			$this->addClientAsset($this->getAppName().".css");
		}
	}

	function getAssetPath($data, $prefix = null) {
		if ($data ===null) {
			return $this->getAppPath(true). $this->getConfig ( 'livedatapath', 'assets' ).DS;
		}

		$hasdot = strpos ( $data, "." ) !== false;
		$type = $hasdot ? substr ( $data, strrpos ( $data, "." ) + 1 ) : '';
		$search = array ();
		$prefix = $prefix ? $prefix : Utils::getFileExt ( $data, false );
		$tpath = null;
		if (! isset ( $this->_assetCache [$type] [$prefix] )) {
			$add = null;
			$def = "";
			$add = null;
			switch (strtolower ( $type )) {
				case "css" :
					$def = "css";
					break;
				case "js" :
					$def = "js";
					break;
				case "gif" :
				case "jpg" :
				case "png" :
				case "jpeg" :
				case "ico" :
					if ($type == "ico") {
						$def = "icon";
					}
					$prefix = $prefix ? $prefix : 'images';

					$type = "images";
					$tpath = "themes" . DS . $this->getConfig ( "themes", "default" );

					break;
			}
			if ($tpath) {
				$search = array_merge ( $search, array (
				$tpath . DS . $type,
				$tpath ) );
			}
			$search = array_merge ( $search, array (
			$type ) );
			$ap =  $this->getConfig ( 'livedatapath', 'assets' );
			$retval = array ();
			$assetpath =$this->getLivePath(false);
			foreach ( $search as $value ) {
				$retval [] =	$assetpath .  $value;
			}
			if (CGAF_DEBUG) {
				foreach ( $search as $value ) {
					$retval[] = $this->getAppPath(true).'DevFiles/assets/'.$value;
				}
			}
			foreach ( $search as $value ) {
				$retval [] = SITE_PATH . $ap . DS . $value;
			}


			$retval []= SITE_PATH . $ap ;
			if (CGAF_DEBUG) {
				foreach ( $search as $value ) {
					$retval[] =CGAF_DEV_PATH.'assets/'.$value;
				}
			}

			if (CGAF_DEBUG) {
				$retval[] = CGAF_DEV_PATH.$ap;
			}
			$this->_assetCache [$type] [$prefix] = $retval;

		}
		return $this->_assetCache [$type] [$prefix];
	}
	public function renderClientAsset($mode=null) {
		$retval = '';


		$retval = $this->getClientAsset()->render(true,$mode);//AssetHelper::renderAsset($assets);

		return $retval;
	}

	function Initialize() {

		if (parent::Initialize ()) {
			if (! defined ( 'APP_URL' )) {
				define ( 'APP_URL', URLHelper::addParam ( BASE_URL, array (
						'__appId' => $this->getAppId () ) ) );
			}
			CGAF::addNamespaceSearchPath("System", $this->getAppPath ().'/classes/');
			CGAF::addNamespaceSearchPath("System", $this->getAppPath ().'/classes/System/');
			CGAF::addNamespaceSearchPath($this->getAppName (), $this->getAppPath () );
			return true;
		}

		return false;
	}

	public function getAgentSuffix() {
		return Utils::getAgentSuffix ();
	}

	/**
	 *
	 * Enter description here ...
	 * @param String $assetName
	 */
	public function getAssetAgent($assetName) {
		$fname = Utils::getFileName ( $assetName );
		$assetAgent = Utils::changeFileName ( $assetName, $fname . $this->getAgentSuffix () );
		return $this->getAsset ( $assetAgent );
	}

	public function getJSEngine() {
		if (! $this->_jsEngine) {
			using ( 'System.Web.JS.Engine.base' );
			using ( 'System.Web.JS.Engine.' . $this->getConfig ( 'app.jsengine', 'jq' ) );
			$c = 'JSEngine' . $this->getConfig ( 'app.jsengine', 'jq' );
			$this->_jsEngine = new $c ( $this );
			$this->_jsEngine->initialize ( $this );
		}
		return $this->_jsEngine;
	}

	protected function getDefaultTemplateParam() {
		return array (
				"baseurl" => BASE_URL, 
				"imageLogo" => $this->getLiveData ( "logo.pg" ) );
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
				return;
			}
		}
		if ($val) {
			$this->_scripts [] = $val;
		}

		return;
	}
	protected function checkInstall() {
		$installPath =  $this->getAppPath(true)."install".DS;
		if (is_dir($installPath) && (CGAF_DEBUG || Session::get("installmode"))) {
			$dest = $this->getLivePath(false);
			Utils::copyFile($installPath.DS."assets", $dest,array('overwrite'=>true),null,null);
		}

	}
	private function cleanComment($c) {
		return '';
	}

	protected function prepareOutputData($s) {
		if (Request::isJSONRequest ()) {
			header ( "Content-Type: application/json, text/javascript;charset=UTF-8", true );
			if (! is_string ( $s )) {
				if (is_object ( $s ) && $s instanceof JSONResult) {
					return $s->Render ( true );
				}
				return JSON::encode ( $s );
			}
			return $s;
		} elseif (Request::isXMLRequest ()) {
			header ( "Content-Type: application/xml;charset=UTF-8", true );
			if (! is_string ( $s )) {
				ppd ( $s );
			}
			return trim ( $s );
		}
		throw new SystemException ( 'Unhandled Request Data Format' );
	}

	function prepareOutput($s) {
		if (Request::isDataRequest ()) {
			return $this->prepareOutputData ( $s );
		}

		$head = $this->renderClientAsset();

		if ($head) {
			$s = str_replace ( '</head>', $head . '</head>', $s );
		}

		$c = 0;

		$stemp = $s;

		if ($this->getConfig ( "output.prepare", false )) {

			$this->_script = '';
			$matches = array ();

			$ss = array ();
			if ($this->getConfig ( 'app.optimizeoutput', true )) {
				$s = preg_replace_callback ( '/(<!--)([^>]*)(>)/i', array (
				$this,
						'cleanComment' ), $s );
				$os = $s;
				$s = trim ( $s );

				$regex = '/((<[\s\/]*script\b[^>]*>)([^>]*)(<\/script>))/i';
				$count = preg_match_all ( $regex, $s, $matches );

				//TODO Fix some error on regex while not valid tag
				$s = preg_replace_callback ( $regex, array (
				$this,
						'parseScript' ), $s );

				$ss = array ();
				//pp($s);
				//ppd($this->_scripts);
				foreach ( $this->_scripts as $script ) {

					if (! isset ( $script ['src'] ))
					continue;

					if (! isset ( $script ['language'] )) {
						$script ['language'] = 'javascript';
					}
					if (! isset ( $script ['type'] )) {
						$script ['type'] = 'text/javascript';
					}
					$ss [] = '<script ' . HTMLUtils::renderAttr ( $script ) . '></script>';
				}

				if ($this->_script) {
					$je = $this->getJSEngine ();
					$ss [] = $je->renderScript ( '(function(){'.$this->_script.'})();' );

				}

				if ($this->_directSript) {
					$ss [] = HTMLUtils::renderScript ( $this->_directSript );
				}
				$c = CGAF_CONTEXT . "Context";
				$log = call_user_func ( array (
				$c,
						'getLog' ) );
				if ($log) {
					$ss [] = '<div id="server-log">' . $log . '</div>';
				}
				//put on the last body
				$stemp = str_ireplace ( "</body>", implode ( "\n", $ss ) . '</body>', $s, $c );
			}
		}
		$stoptimer = time () + microtime ();
		$timer = round ( $stoptimer - CGAF::startTime (), 4 );
		if (! Request::isDataRequest ()) {
			if ($timer) {
				$stemp = str_ireplace ( "{runtime}", $timer, $stemp );
			}
		}
		if (! $c && $ss) {

			$stemp = $s . implode ( "\n", $ss );
		}
		if ($this->getConfig ( "output.prepare", false )) {
			//return $stemp;
			//using('libs.minifier.minify.min.lib.Minify.HTML');
			return preg_replace ( "/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "", $stemp );
		}
		return $stemp;

	}

	public function handleCommetRequest() {
		Response::write ( "halooo" );
		return true;
	}

	protected function initRequest() {
		if (! Request::isDataRequest ()) {
			if (Request::isSupport ( 'javascript', true )) {
				$this->getJSEngine ();
			}
		}
		return true;
	}
	protected function renderHeader() {
		return "<head>";
	}
	function renderMetaHead() {
		$retval = '';
		foreach ($this->_metas as $value) {
			$retval .=  '<'.$value['tag'] . ' '. (isset($value['name']) ? ' name="'.$value['name'].'" ' : ' ');
			$retval .= HTMLUtils::renderAttr($value['attr']);
			$retval .= '/>';
		}
		return $retval;
	}
	public function Run() {
		$a = Request::get ( "__url" );
		$a = explode ( "/", $a );
		if ($a [0] == "_appList") {
			Response::StartBuffer ();
			include Utils::ToDirectory ( $this->getSharedPath () . "Views/applist.php" );
			return Response::EndBuffer ( false );
		}
		return false;
	}

}
?>

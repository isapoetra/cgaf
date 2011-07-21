<?php
if (! defined ( "CGAF" ))
die ( "Restricted Access" );

if (class_exists('WebApplication',false)) {
	//TODO move to context aware location
	class MVCApplication extends WebApplication {
		protected $_controller;
		protected $_action = "Index";
		private $_models = array ();
		protected $_route;
		protected $_viewPath;

		private $_messages;
		private $_ignoreJSMin = array ();
		private $_searchPath =array();
		function __construct($appPath, $appName) {

			parent::__construct ( $appPath, $appName );
			if (! $this->getConfig ( 'app.internalstorage' )) {
				$path = $this->getAppPath () . '/protected/';
				$this->setConfig ( 'app.internalstorage', $path );
			}
		}

		function getControllerLookup($for) {
			$path = Utils::getDirFiles ( $this->getAppPath () . DS . 'Controllers' . DS );
			$retval = array ();
			foreach ( $path as $p ) {
				$fname = Utils::getFileName ( $p, false );
				if ($this->getACL ()->isAllow ( $fname, 'controller', $for )) {
					$retval [] = array (
						'key' => $fname,
						'value' => ucfirst ( $fname ),
						'descr' => __ ( 'controller.' . $fname . '.descr' ) );
				}
			}
			return $retval;
		}
		public function HandleModuleNotFound($m,$u=null,$a=null) {
			$mpath = ModuleManager::getModulePath($m);
			if (!$mpath) {
				throw new AccessDeniedException();
			}
			$this->addSearchPath($mpath);
			$f = $this->findFile("index", "Views",true);
			if ($f) {
				return TemplateHelper::renderFile($f);
			}
			throw new SystemException("Unbable to find module".$m);
		}
		private function _cacheJS($f, $target, $minifymin = false) {
			$fname = $this->getAsset ( $f, "js" );
			if (! $fname) {
				//get from minified version but no source
				$fname = $this->getAsset ( Utils::changeFileExt ( $f, 'min.js' ), "js" );
				if ($fname) {
					$content = file_get_contents ( $fname );
					if ($minifymin) {
						try {
							$content = CGAF_DEBUG ? file_get_contents ( $fname ) : JSUtils::Pack ( file_get_contents ( $fname ) );
						} catch ( Exception $e ) {
							die ( $e->getMessage () . ' on file ' . $fname );
						}
					}
				}
			} else {
				try {
					$content = CGAF_DEBUG ? file_get_contents ( $fname ) : JSUtils::Pack ( file_get_contents ( $fname ) );
				} catch ( Exception $e ) {
					die ( $e->getMessage () . ' on file ' . $fname );
				}

			}
			if ($fname) {
				return $this->getCacheManager ()->putString ( "\n" . $content, CGAF_DEBUG ? basename ( $fname ) : $target, 'js', null, CGAF_DEBUG ? false : true );
			}

		}

		protected function cacheJS($arr, $target, $force = false) {
			if (CGAF_DEBUG) {
				$target = str_ireplace ( 'min.js', 'js', $target );
			} else {
				$target = Utils::changeFileExt ( $target, 'min.js' );
			}
			$live = $this->getLiveAsset ( $target, 'js' );

			if (! $live) {

				$target = basename ( $target );
				$jsname = $this->getCacheManager ()->get ( $target, 'js' );

				if ($force || ! $jsname) {

					if (is_array ( $arr )) {
						foreach ( $arr as $f ) {
							$min = ! in_array ( $f, $this->_ignoreJSMin );

							if (Utils::isLive ( $f )) {
								$jsname [] = $f;
							} else {
								$jsname [] = $this->_cacheJS ( $f, $target, $min );
							}

						}
					} else {
						$min = ! in_array ( $arr, $this->_ignoreJSMin );
						$jsname = $this->_cacheJS ( $arr, $target, $min );
					}
				}
				return $this->getLiveData ( $jsname );
			}
			return $live;
		}

		protected function cacheCSS($css, $target, $force = false) {
			if (! $target && is_string ( $css )) {
				$fname = Utils::getFileName ( $css );
				$target = Utils::changeFileName ( $css, $fname . $this->getAgentSuffix () );
			}
			$fname = $this->getCacheManager ()->get ( $target, 'css' );
			//pp($fname);
			if (! $fname || $force) {
				if ($fname && is_file ( $fname )) {
					unlink ( $fname );
				}
				$parsed = array ();
				if (is_array ( $css )) {

					foreach ( $css as $v ) {
						$parsed [] = $this->getAsset ( $v ['url'] );
						$ta = $this->getAssetAgent ( $v ['url'] );
						if ($ta) {
							$parsed [] = $ta;
						}
					}
				} else {
					$tcss = $this->getAsset ( $css );
					if ($tcss) {
						$parsed [] = $tcss;
					}
					$tcss = $this->getAssetAgent ( $css );
					if ($tcss) {
						$parsed [] = $tcss;
					}
				}
				if (count ( $parsed )) {
					$content = WebUtils::parseCSS ( $parsed, $fname, CGAF_DEBUG == false );
					$fname = $this->getCacheManager ()->putString ( $content, $target, 'css' );
				}
			}
			if ($fname) {
				return $this->getLiveData ( $fname );
			}
			return null;

		}

		function getController($controllerName = null) {
			if ($controllerName == null) {
				$controllerName = $this->getRoute ( '_c' );
			}

			if (! $this->isAllow ( $controllerName, 'controller' )) {
				throw new AccessDeniedException ();
			}
			$instance = null;

			if ($controllerName === $this->getRoute ( '_c' )) {
				if ($this->_controller !== null) {
					$instance = $this->_controller;
				}
			}
			try {
				if (! $instance) {
					$instance = $this->getClassInstance ( $controllerName, "Controller", $this );
					if (!$instance) {
						throw new SystemException("Unable to Find controller %s",$controllerName);
					}
				}

				if (! $instance->isAllow ( ACLHelper::ACCESS_VIEW )) {
					throw new AccessDeniedException ();
				}

				if ($controllerName === $this->getRoute ( '_c' )) {
					if ($this->_controller === null) {
						$this->_controller = $instance;
					}
				}
			} catch ( Exception $e ) {
				$this->_lastError = $e->getMessage ();
			}

			return $instance;
		}

		function initSession() {
			parent::initSession ();
		}

		function getRoute($arg = null) {
			if ($arg !== null) {
				return $this->_route [$arg];
			}
			return $this->_route;
		}

		/*function contentCallback($rpath, $content, $id, $group) {
		 $ext = Utils::getFileExt ( $id, false );
		 if (! $ext || is_numeric ( $ext )) {
		 $ext = $group;
		 }
		 switch (trim ( $ext )) {
		 case "js" :

		 $paths = $rpath;
		 if (is_array ( $rpath )) {
		 $paths = array ();
		 foreach ( $rpath as $v ) {
		 if (is_array ( $v )) {
		 if (isset ( $v ['url'] )) {
		 $paths [] = $v ['url'];
		 }
		 } else {
		 $paths [] = $v;
		 }
		 }
		 $rpath = $paths;
		 }

		 return $this->cacheJS ( $rpath, $id );
		 break;
		 case 'css' :
		 if (is_array ( $rpath )) {
		 return $this->cacheCSS ( $rpath, $id );
		 } else {
		 return $this->cacheCSS ( $rpath, null );
		 }
		 break;
		 case 'xml' :
		 if (is_file ( $id )) {
		 $asset = $id;
		 } else {
		 $asset = $this->getAsset ( $id, $group );
		 }
		 if ($asset) {
		 $retval = ProjectManager::build ( $asset );
		 return Utils::LocalToLive ( $retval, '' );
		 } else {
		 pp ( $id );
		 }
		 break;
		 default :

		 if (is_string ( $rpath ) && Utils::isLive ( $rpath )) {
		 return $rpath;
		 }
		 throw new SystemException ( 'unhandled data type ' . $ext . " on class " . get_class ( $this ) . pp ( $rpath, true ) );

		 }
		 return $content;
		 }*/
		protected function initRequest() {
			parent::initRequest();
			$rname = $this->getController () ? $this->getController ()->getRouteName () : 'Home';
			if (! Request::isDataRequest ()) {
				$title = $this->getConfig ( $rname . '.title', ucwords ( $rname ) );
				$this->Assign ( 'title', $title );
			}
			if (!Request::isAJAXRequest()) {
				$this->addClientAsset( $this->getRoute ( '_c' ) . '.css' );
			}
			//ppd($this->getClientAssets());
			$this->Assign ( "token", $this->getToken () );
		}



		function getSharedPath() {
			return dirname ( __FILE__ ) . DS . "shared" . DS;
		}

		protected function checkInstall() {
			parent::checkInstall();
			/*if (! CGAF_DEBUG) {
			 $path = $this->getConfig ( "ViewPath", $this->getAppPath () . DS . "Views" );
			 if (! is_readable ( $path )) {
			 Utils::copyFile ( dirname ( __FILE__ ) . DS . "base/Views", $path );
			 }
			 $path = $this->getConfig ( "ControllerPath", $this->getAppPath () . DS . "Controllers" );
			 if (! is_readable ( $path )) {
			 Utils::copyFile ( dirname ( __FILE__ ) . DS . "base/Controllers", $path );
			 }
			 $path = $this->getConfig ( "ModelsPath", $this->getAppPath () . DS . "Models" );
			 if (! is_readable ( $path )) {
			 Utils::copyFile ( dirname ( __FILE__ ) . DS . "base/Models", $path );
			 }
			 }*/
		}

		function isAllow($id, $group, $access = 'view') {
			if ($id === 'home' && $group === 'controller' && $access === 'view') {
				return true;
			}

			return parent::isAllow ( $id, $group, $access );
		}

		public function Initialize() {

			if ($this->isInitialized ()) {
				return true;
			}
			if (parent::Initialize ()) {
				AppModule::Initialize( $this );
				$this->_route = MVCHelper::getRoute ();
				$this->_action = $this->_route ["_a"];
				$libs = $this->getConfig ( 'apps.libs' );
				$path = $this->getAppPath ();
				CGAF::addClassPath ( 'Models', array (
				$path . DS . 'Models',
				CGAF_CORE_PATH . DS . 'Models' ), false );

				/*CGAF::addClassPath ( 'View', array (
				 $path . DS . 'Views',
				 CGAF_CORE_PATH . DS . 'Views' ), false );*/

				CGAF::addClassPath ( 'Controller', array (
				$path . DS . 'Controllers',
				CGAF_CORE_PATH . DS . 'Controllers' ), false );

				if ($libs) {
					using ( $libs );
				}

				$this->addSearchPath($this->getAppPath ());
				$this->addSearchPath(CGAF_CORE_PATH);
				//$this->addSearchPath(CGAF_PATH . "System" . DS );
				return true;
			}
			return false;
		}

		function getMessages() {
			return $this->_messages;
		}

		/**
		 *
		 * @param $message
		 * @return unknown_type
		 * @deprecated
		 */
		function addMessage($message) {
			if ($this->_messages == null) {
				$this->_messages = array ();
			}
			$this->_messages [] = $message;
		}
		protected function addSearchPath($value) {
			if (is_array($value)) {
				foreach ($value as $v) {
					$this->addSearchPath($v);
				}
				return;
			}
			if (!in_array( $value,$this->_searchPath)) {
				$this->_searchPath = array_merge($this->_searchPath,array($value));
			}
		}
		function getSearchPath($fname, $suffix) {
			$fname = Utils::ToDirectory ( $fname );
			$retval = array();
			foreach ($this->_searchPath as $v) {
				$retval[] = Utils::ToDirectory($v.DS.($suffix ? $suffix . DS : ""));
			}
			return $retval;
			/*pp($retval);
			 ppd($this->_searchPath);
			 return array (
			 $this->getAppPath () . DS . ($suffix ? $suffix . DS : ""),
			 CGAF_CORE_PATH . DS . ($suffix ? $suffix . DS : ""),
			 CGAF_PATH . "System" . DS );*/
		}

		function findFile($fname, $suffix, $throw = false) {
			//find from file
			$searchs = $this->getSearchPath ( $fname, $suffix );
			foreach ( $searchs as $f ) {
				$f = $f . $fname . CGAF_CLASS_EXT;
				if (is_file ( $f )) {
					return $f;
				}
			}
			if ($throw) {
				if (CGAF_DEBUG) {
					//pp(debug_backtrace(false));
					ppd ( $searchs );
				}
				throw new SystemException ( "error.filenotfound", $fname . ' On Class ' . get_class ( $this ) );
			}
			return null;
		}

		function getClassPrefix() {
			return $this->getConfig ( "class.prefix", Utils::toClassName ( $this->getAppName () ) );
		}

		function getClassInstance($className, $suffix, $args, $find = true) {
			$c = CGAF::getClassInstance ( $className, $suffix, $args, $this->getAppName () );
			if (! $c) {
				$c = CGAF::getClassInstance ( $className, $suffix, $args );
			}
			return $c;
		}

		/**
		 *
		 * @param $model
		 * @return MVCModel
		 */
		function getModel($model) {
			if (isset ( $this->_models [$model] )) {
				$this->_models [$model]->setAppOwner ( $this );

				return $this->_models [$model];
			}
			if (!using("Models.".$model)) {
				$file = $this->getAppPath () . DS . "Models/" . $model . ".php";
				if (is_readable ( $file )) {
					CGAF::Using ( $file );
				}
			}
			$instance = $this->getClassInstance ( $model, "Model", $this );
			if (!$instance) {
				throw new SystemException("Unable to find model ".$model);
			}
			$instance->setAppOwner ( $this );
			$this->_models [$model] = $instance;
			return $this->_models [$model];
		}

		public function getInternalData($path, $create = false) {
			$iPath = $this->getConfig ( "app.internalstorage" ) . Utils::ToDirectory ( "/" . $path );
			if (is_readable ( $iPath )) {

				return $iPath;
			} else {
				if ($create) {
					return Utils::makeDir ( $iPath );
				}
				Logger::Warning ( "Unable to find Internal Data $iPath" );
			}
			return null;
		}

		function getAuthInfo() {
			return Session::get ( "__logonInfo" );

		}

		function getDefaultController() {
			return $this->getConfig ( "defaultController", "home" );
		}

		protected function handleRun() {
			$c = $this->_route ["_c"];
			switch (strtolower ( $c )) {
				case '_loc' :
					$id = Request::get ( 'id' );
					if ($id) {
						$this->getLocale ()->setLocale ( $id );
						Response::Redirect ( BASE_URL );
					} else {
						$this->_route ['_c'] = 'locale';
					}
					//$loc = $this->getController('locale');
					//return $loc->Index();
					break;
				case '_applist' :
					$this->_route ['_c'] = 'home';
					$this->_route ['_a'] = 'applist';
					$this->_action = 'applist';
			}

			return false;
		}

		protected function initRun() {
			parent::initRun ();
			if (Request::get ( "__init" )) {
				Session::set ( "hasinit", true );
				$mode = Request::get ( "__js" ) == "true" ? true : false;
				Session::set ( "__jsmode", $mode );
			}
			Session::remove ( "__route" );
			if (Request::isAJAXRequest ()) {
				Session::set ( "__route", $this->getRoute () );
			}
			$m=Request::get("__m");
			if ($m) {
				$mpath = ModuleManager::getModulePath($m);
				$this->addSearchPath($mpath);
			}
		}

		function getRequestAction() {
			return $this->getRoute ( "_a" );
		}
		protected function renderHeader() {
			return $this->getController()->getView ( 'header' );
		}
		protected function handleService($serviceName) {
			return false;
		}
		protected function handleRequest() {
			//$this->getTemplate ();
			try {

				$controller = $this->getController ();

				if (! $controller) {
					if ($this->parent) {
						return 'no Controller';
					}
					if (! CGAF_DEBUG) {
						return Response::RedirectToLogin ( $this->_lastError );
					} else {
						throw new SystemException ( $this->_lastError );
					}
				}

				$this->_action = $controller->getActionAlias ( $this->_action );
				$this->_route ["_a"] = $this->_action;
				if (method_exists ( $controller, $this->_action )) {
					$action = $this->_action;
				} else {
					if (Request::isDataRequest()){
						$r = $this->handleService($this->getRoute('_c').'-'.$this->getRoute('_a'));
						if (!$r) {
							throw new Exception('Unhandled action '.$this->_action.' On Controller '.$this->getRoute('_c'));
						}
						$this->Assign('content',$r);
					}else{
						$action = "index";
					}
				}
				$content = $this->getVars("content");
				if (!$content) {
					if ($controller->isAllow ( $action )) {
						$params = array ();
						$controller->initAction ( $action, $params );
						$content = $controller->{$action} ( $params, null, null, null );
					} elseif (! ($content = $controller->handleAccessDenied ( $action ))) {
						$content = $controller->getLastError () ? $controller->getLastError () : "access to action $action is denied on controller " . get_class ( $controller );
					}
				}
			} catch ( Exception $x ) {

				throw $x;
			}

			$retval = '';
			if (! Request::isDataRequest ()) {
				$retval =  $this->renderHeader();
			}

			$retval .= $content;
			if (! Request::isDataRequest ()) {
				$retval .= $controller->getView ( 'footer' );
			}
			return $retval;
		}

		function Run() {
			$this->onSessionEvent ( new SessionEvent ( Session::getInstance (), SessionEvent::SESSION_STARTED ) );
			$this->initRun ();

			try {
				if (! $retval = $this->handleRun ()) {
					$action = $this->_action;
					$this->initRequest();
					$retval = $this->handleRequest ();
				}
			} catch ( Exception $e ) {
				throw $e;
				if (Request::isJSONRequest ()) {
					$retval = new JSONResult ( - 1, $e->getMessage () );
				} else {
					$retval = $e;
				}
			}
			return $this->prepareOutput($retval);

		}

		function renderMenu($position) {
			return $this->getController ()->renderMenu ( $position );
		}

		function renderContent($location, $controller = null, $returnori = false, $return = true) {
			if ($controller === null) {
				$controller = $this->getController ()->getControllerName ();
			}
			$m = $this->getModel ( "content" );

			$m->clear ();
			$m->where ( "state=1" );
			$m->where ( "content_controller=" . $m->quote ( $controller ) );
			$m->where ( "position=" . $m->quote ( $location ) );
			$m->orderBy ( 'idx' );
			$rows = $m->loadAll ();
			if (! count ( $rows )) {
				return null;
			}

			$retOri = array ();
			$content = null;
			$menus = array ();
			foreach ( $rows as $row ) {
				$ctl = null;
				$hcontent = null;
				$retval = null;
				/*
				 * 1 	: view view handled by initAction method on controller
				 * 2 	: direct access to controller
				 * 3	: Direct link
				 * 4	: render menu
				 */
				switch ($row->content_type) {
					case 2 :
						//direct action to controller
						try {
							$ctl = $this->getClassInstance ( $row->controller, "Controller", $this );
							$row->actions = $row->actions ? $row->actions : "index";
							if (method_exists ( $ctl, $row->actions ) && $ctl->isAllow ( $row->actions )) {
								$hcontent = $ctl->{$row->actions} ( true, Utils::DBDataToParam ( $row->params ) );
							} elseif (! method_exists ( $ctl, $row->actions ) && CGAF_DEBUG) {
								$hcontent = HTMLUtils::renderError ( 'method [' . $row->actions . '] not found in class ' . $row->controller );
							}
						} catch ( Exception $e ) {
							if (CGAF_DEBUG) {
								$hcontent = HTMLUtils::renderError ( $e->getMessage () );
							} else {
								continue;
							}
						}
						break;
					case 3 :
						try {
							if ($row->controller !== null && $this->isAllow ( $row->controller, "controller" )) { //make sure user has access to controller
								$ctl = $this->getClassInstance ( $row->controller, "Controller", $this );
							}
						} catch ( Exception $e ) {
							$ctl = null;
						}
						if ($row->controller !== null) {
							if ($ctl && $ctl->isAllow ( $row->actions )) { //cek security by internal controller
								$menus [] = '<a href="' . BASE_URL . '/' . $row->controller . '/' . $row->actions . '">' . $row->content_title . '</a>';
							}
						}

						break;
					case 4 :
						//renderMenu
						try {
							if ($row->controller !== null && $this->isAllow ( $row->controller, "controller" )) { //make sure user has access to controller
								$ctl = $this->getClassInstance ( $row->controller, "Controller", $this );
							}
						} catch ( Exception $e ) {
							$ctl = null;
						}
						if ($ctl && $row->controller !== null) {
							$hcontent = $ctl->renderMenu ( $row->actions );
						}
						break;
					case 1 :
					default :
						try {
							if ($row->controller !== null && $this->isAllow ( $row->controller, "controller" )) {
								$ctl = $this->getClassInstance ( $row->controller, "Controller", $this );
							}
						} catch ( Exception $e ) {
							$ctl = null;
						}
						if ($ctl !== null) {
							$hcontent = null;
							$row->__content = "";
							$action = $row->actions ? $row->actions : "index";
							$params = $row->params ? unserialize ( $row->params ) : array ();
							$params ["_position"] = $location;

							//if (is_callable(array($ctl,$action))) {
							//	$hcontent = $ctl->$action($params);
							//}else
							if ($ctl->initAction ( $action, $params )) {
								$hcontent = $ctl->render ( array (
									"_a" => $action ), $params, true );
							}

						}
				}
				if ($hcontent) {
					$content .= "<div class=\"$location-item {$row->controller} clearfix\">";
					$content .= "	<div class=\"ui-widget-header bar\">";
					if ($row->content_title) {
						$content .= "	<h4>" . __ ( $row->content_title ) . "</h4>";
					}
					$content .= "	</div>";
					$content .= "<div  class=\"delim\"></div>";
					$row->__content = $hcontent;

					$rcontent = $row->__content;
					if (is_object ( $rcontent ) && $rcontent instanceof IRenderable) {
						$rcontent = $rcontent->render ( true );
					}
					$content .= "<div class=\"content ui-widget-content\"><div>" . $rcontent . "</div></div>";
					$content .= "</div>";

					$retOri [] = $row;
				}
				unset ( $ctl );
			}

			if (count ( $menus )) {
				$c = "<div class=\"$location-item  clearfix menus\">";
				$c .= "	<div class=\"ui-widget-header bar\">";
				$c .= "		<h4>" . __ ( 'Actions' ) . "</h4>";
				$c .= "	</div>";
				$c .= "	<div  class=\"delim\"></div>";
				$c .= "	<div class=\"content\"><div>";
				$c .= "	<ul>";
				foreach ( $menus as $m ) {
					$c .= '<li>' . $m . '</li>';
				}
				$c .= "	</ul>";
				$c .= "	</div>";
				$c .= '</div>';
				$content = $c . $content;
			}
			if ($returnori) {
				return $retOri;
			}

			if ($content) {
				$retval = "<div class=\"content-$location\">" . $content . "</div>";
			}
			if (! $return) {
				Response::write ( $retval );
			}
			return $retval;
		}

		function renderControllerMenu($position = "top") {
			return $this->getController ()->renderMenu ( $this->getController ()->getRouteName () . "-$position", "menu2ndlevel" );
		}

		function renderView($view, $a = null, $args = null) {
			return $this->getController ()->getView ( $view, $a, $args );
		}

		function removeSession($sid) {
			if ($sid !== session_id ()) {
				Session::getInstance ()->destroy ( $sid );
				$this->onSessionEvent ( new SessionEvent ( null, SessionEvent::DESTROY ), $sid );
				return __ ( 'session.destroyed', 'Killed' );
			} else {
				return __ ( 'user.suicide', 'arrrrrrrrrrrrrrrrghhh....' );
			}
		}

		function getUserInfo($id) {
			static $uinfo;
			if (isset ( $uinfo [$id] )) {
				return $uinfo [$id];
			}
			$uinfo [$id] = new UserInfo ( $this, $id );
			return $uinfo [$id];

		}

		public function handleError($ex) {
			if (Request::isJSONRequest ()) {
				$json = new JSONResult ( false, $ex->getMessage () );
				if (class_exists ( 'response', false )) {
					Response::write ( $json->Render ( true ) );
				} else {
					echo $json->Render ( true );
				}
			} elseif (Request::isAJAXRequest ()) {
				ppd ( $ex );

			} else {


				Logger::Error ( "[%s] %s", get_class ( $ex ), $ex->getMessage () );
			}
		}

		function onSessionEvent($event, $sid = null) {

		}
	}
}else {
	class MVCApplication extends ConsoleApplication {

	}
}
?>
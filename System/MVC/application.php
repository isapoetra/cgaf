<?php
namespace System\MVC;
use System\ACL\ACLHelper;
use System\DB\DBUtil;
use System\JSON\JSONResult;
use \System, \CGAF, \Utils;
use \Request;
use \Logger;
use System\Exceptions\SystemException;
use System\Exceptions\AccessDeniedException;
use System\Session\Session;
use System\Session\SessionEvent;
use System\Web\WebUtils;
use System\Applications\WebApplication;
use System\Web\Utils\HTMLUtils;
use \URLHelper;
use \ModuleManager;
use System\Template\TemplateHelper;
use System\Web\JS\CGAFJS;
use \Response;
if (System::isWebContext()) {
	using('System.Applications.web');
	//TODO move to context aware location
	class Application extends WebApplication {
		protected $_controller;
		protected $_action = "Index";
		private $_models = array();
		protected $_route = array();
		protected $_viewPath;
		protected $_searchPath = array();
		private $_messages;
		private $_ignoreJSMin = array();
		function __construct($appPath, $appName) {
			parent::__construct($appPath, $appName);
			if (!$this->getConfig('app.internalstorage')) {
				$path = $this->getAppPath() . '/protected/';
				$this->setConfig('app.internalstorage', $path);
			}
		}
		function uninstall() {
			if ($this->isAllow('manage', 'system', ACLHelper::ACCESS_MANAGE)) {
				$appId = $this->getAppId();
				if ($appId !== '__cgaf') {
					$f = CGAF::getInternalStorage('db', false, true) . '/uninstall-app.sql';
					if (is_file($f)) {
						DBUtil::execScript($f, CGAF::getDBConnection(), array(
								'app_id' => $appId));
					}
				}
			}
		}
		function getControllerLookup($for) {
			$path = Utils::getDirFiles($this->getAppPath() . DS . 'Controllers' . DS);
			$retval = array();
			foreach ($path as $p) {
				$fname = Utils::getFileName($p, false);
				if ($this->getACL()->isAllow($fname, 'controller', $for)) {
					$retval[] = array(
							'key' => $fname,
							'value' => ucfirst($fname),
							'descr' => __('controller.' . $fname . '.descr'));
				}
			}
			return $retval;
		}
		public function HandleModuleNotFound($m, $u = null, $a = null) {
			$mpath = ModuleManager::getModulePath($m);
			if (!$mpath) {
				throw new AccessDeniedException();
			}
			$this->addSearchPath($mpath);
			$f = $this->findFile("index", "Views", false);
			if ($f) {
				return TemplateHelper::renderFile($f, null, $this->getController());
			}
			return parent::handleModuleNotFound($m);
		}
		private function _cacheJS($f, $target, $minifymin = false) {
			$fname = $this->getAsset($f, "js");
			if (!$fname) {
				//get from minified version but no source
				$fname = $this->getAsset(Utils::changeFileExt($f, 'min.js'), "js");
				if ($fname) {
					$content = file_get_contents($fname);
					if ($minifymin) {
						try {
							$content = $this->isDebugMode() ? file_get_contents($fname) : JSUtils::Pack(file_get_contents($fname));
						} catch (Exception $e) {
							die($e->getMessage() . ' on file ' . $fname);
						}
					}
				}
			} else {
				try {
					$content = $this->isDebugMode() ? file_get_contents($fname) : JSUtils::Pack(file_get_contents($fname));
				} catch (Exception $e) {
					die($e->getMessage() . ' on file ' . $fname);
				}
			}
			if ($fname) {
				return $this->getCacheManager()->putString("\n" . $content, $this->isDebugMode() ? basename($fname) : $target, 'js', null, $this->isDebugMode() ? false : true);
			}
		}
		protected function cacheJS($arr, $target, $force = false) {
			if ($this->isDebugMode()) {
				$target = str_ireplace('min.js', 'js', $target);
			} else {
				$target = Utils::changeFileExt($target, 'min.js');
			}
			$live = $this->getLiveAsset($target, 'js');
			if (!$live) {
				$target = basename($target);
				$jsname = $this->getCacheManager()->get($target, 'js');
				if ($force || !$jsname) {
					if (is_array($arr)) {
						foreach ($arr as $f) {
							$min = !in_array($f, $this->_ignoreJSMin);
							if (Utils::isLive($f)) {
								$jsname[] = $f;
							} else {
								$jsname[] = $this->_cacheJS($f, $target, $min);
							}
						}
					} else {
						$min = !in_array($arr, $this->_ignoreJSMin);
						$jsname = $this->_cacheJS($arr, $target, $min);
					}
				}
				return $this->getLiveData($jsname);
			}
			return $live;
		}
		protected function cacheCSS($css, $target, $force = false) {
			if (!$target && is_string($css)) {
				$fname = Utils::getFileName($css);
				$target = Utils::changeFileName($css, $fname . $this->getAgentSuffix());
			}
			$fname = $this->getCacheManager()->get($target, 'css');
			//pp($fname);
			if (!$fname || $force) {
				if ($fname && is_file($fname)) {
					unlink($fname);
				}
				$parsed = array();
				if (is_array($css)) {
					foreach ($css as $v) {
						$parsed[] = $this->getAsset($v['url']);
						$ta = $this->getAssetAgent($v['url']);
						if ($ta) {
							$parsed[] = $ta;
						}
					}
				} else {
					$tcss = $this->getAsset($css);
					if ($tcss) {
						$parsed[] = $tcss;
					}
					$tcss = $this->getAssetAgent($css);
					if ($tcss) {
						$parsed[] = $tcss;
					}
				}
				if (count($parsed)) {
					$content = WebUtils::parseCSS($parsed, $fname, $this->isDebugMode() == false);
					$fname = $this->getCacheManager()->putString($content, $target, 'css');
				}
			}
			if ($fname) {
				return $this->getLiveData($fname);
			}
			return null;
		}
		private function getControllerInstance($controllerName) {
			CGAF::Using('Controller.' . $controllerName, true);
			$cname = $this->getClassNameFor($controllerName, 'Controller', 'System\\Controllers');
			if (!$cname) {
				throw new SystemException("Unable to Find controller %s", $controllerName);
			}
			$instance = new $cname($this);
			if (!$instance) {
				throw new SystemException("Unable to Find controller %s", $controllerName);
			}
			return $instance;
		}
		protected function getMainController() {
			if ($this->_controller === null) {
				try {
					if (Request::get('__m')) {
						$this->_controller = \ModuleManager::getModuleInstance(Request::get('__m'));
					} else {
						$this->_controller = $this->getControllerInstance($this->getRoute('_c'));
					}
				} catch (\Exception $e) {
					$this->_route['_c'] = 'home';
					$this->_controller = $this->getControllerInstance('home');
				}
			}
			return $this->_controller;
		}
		function getController($controllerName = null, $throw = true) {
			$instance = null;
			$controllerName = $controllerName ? $controllerName : Request::get('__m');
			$controllerName = $controllerName ? $controllerName : $this->getRoute('_c');
			if ($controllerName === $this->getRoute('_c')) {
				return $this->getMainController();
			}
			try {
				$instance = $this->getControllerInstance($controllerName);
			} catch (\Exception $e) {
				if ($throw) {
					throw $e;
				}
				\Logger::Warning($e->getMessage());
				return null;
			}
			return $instance;
		}
		function initSession() {
			parent::initSession();
		}
		function getRoute($arg = null) {
			if ($arg !== null) {
				return $this->_route[$arg];
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
			$_crumbs = array();
			$route = $this->getRoute();
			if ($route['_c'] !== 'home') {
				$_crumbs[] = array(
						'url' => APP_URL,
						'class' => 'home');
			}
			if ($route['_c'] !== 'home') {
				$_crumbs[] = array(
						'title' => ucwords(__($route['_c'])),
						'url' => URLHelper::add(APP_URL, $route['_c']));
			}
			if ($route['_a'] !== 'index') {
				$_crumbs[] = array(
						'title' => ucwords(__($route['_c'] . '.' . $route['_c'], $route['_a'])),
						'url' => URLHelper::add(APP_URL, $route['_c'] . '/' . $route['_a']));
			}
			//Session::set('app.isfromhome', false);
			if ($route['_c'] === 'home') {
				Session::set('app.isfromhome', true);
			}
			CGAFJS::initialize($this);
			parent::initRequest();
			$this->addCrumbs($_crumbs);
			$controller = null;
			try {
				$controller = $this->getController();
			} catch (\Exception $e) {
				$this->_lastError = $e->getMessage();
			}
			$rname = $controller ? $controller->getControllerName() : 'Home';
			if (!Request::isDataRequest() && !$this->getVars('title')) {
				$title = $this->getConfig($rname . '.title', ucwords(__($rname . '.site.title', $rname)));
				$deftitle = $this->getAppId() === \CGAF::APP_ID ? \CGAF::getConfig('cgaf.title') : $this->getConfig('app.title', $this->getAppName());
				$this->Assign('title', $this->getConfig('app.title', $deftitle) . ' ::: ' . $title);
			}
			if (!Request::isDataRequest()) {
				$this->addClientAsset($this->getAppName() . '.js');
				$this->addClientAsset($this->getRoute('_c') . '.css');
				$this->getAppOwner()->addClientAsset($this->getRoute('_a') . '.css');
			}
			$this->Assign("token", $this->getToken());
		}
		function isFromHome() {
			return Session::get('app.isfromhome');
		}
		function getSharedPath() {
			return dirname(__FILE__) . DS . "shared" . DS;
		}
		/**
		 * (non-PHPdoc)
		 * @see System\Applications.WebApplication::checkInstall()
		 */
		protected function checkInstall() {
			parent::checkInstall();
		}
		/**
		 * (non-PHPdoc)
		 * @see System\Applications.AbstractApplication::isAllow()
		 */
		function isAllow($id, $group, $access = 'view') {
			switch ($access) {
			case 'view':
			case ACLHelper::ACCESS_VIEW:
				switch ($group) {
				case 'controller':
					switch ($id) {
					case 'about':
					case 'auth':
					case 'home':
					case 'asset':
					case 'search':
						return true;
						break;
					}
				}
				break;
			}
			return parent::isAllow($id, $group, $access);
		}
		public function Initialize() {
			if ($this->isInitialized()) {
				return true;
			}
			if (parent::Initialize()) {
				$this->_route = MVCHelper::getRoute();
				$this->_action = $this->_route["_a"];
				$libs = $this->getConfig('apps.libs');
				$path = $this->getAppPath();
				$this->_searchPath = array(
						$path,
						CGAF_SHARED_PATH);
				CGAF::addClassPath($this->getAppName(), $path . DS . 'classes' . DS);
				CGAF::addClassPath('System', $path . DS, false);
				CGAF::addClassPath('Controller', $path . DS . 'Controllers' . DS, false);
				CGAF::addClassPath('Controllers', $path . DS . 'Controllers' . DS, false);
				CGAF::addClassPath('Models', $path . DS . 'Models' . DS, false);
				CGAF::addClassPath('Modules', $path . DS . 'Modules' . DS, false);
				if ($libs) {
					using($libs);
				}
				return true;
			}
			$this->dispatchEvent(new SessionEvent($this, SessionEvent::DESTROY));
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
				$this->_messages = array();
			}
			$this->_messages[] = $message;
		}
		protected function addSearchPath($value) {
			if (is_array($value)) {
				foreach ($value as $v) {
					$this->addSearchPath($v);
				}
				return;
			}
			if (!in_array($value, $this->_searchPath)) {
				$this->_searchPath = array_merge(array(
						$value), $this->_searchPath);
			}
		}
		function getSearchPath($fname, $suffix) {
			$fname = Utils::ToDirectory($fname);
			$retval = array();
			foreach ($this->_searchPath as $v) {
				$retval[] = Utils::ToDirectory($v . DS . ($suffix ? $suffix . DS : ""));
			}
			foreach ($this->_searchPath as $v) {
				$retval[] = Utils::ToDirectory($v);
			}
			return $retval;
		}
		function findFile($fname, $suffix, $throw = false) {
			//find from file
			$searchs = $this->getSearchPath($fname, $suffix);
			foreach ($searchs as $f) {
				$f = $f . $fname . CGAF_CLASS_EXT;
				if (is_file($f)) {
					if ($suffix && !\Strings::Contains($f, $suffix))
						continue;
					return $f;
				}
			}
			if ($throw) {
				if ($this->isDebugMode()) {
					pp($fname);
					pp($suffix);
					//pp(debug_backtrace(false));
					ppd($searchs);
				}
				throw new SystemException("error.filenotfound", $fname . ' On Class ' . get_class($this));
			}
			return null;
		}
		function getClassPrefix() {
			return $this->getConfig("class.prefix", Utils::toClassName($this->getAppName()));
		}
		function getClassInstance($className, $suffix, $args, $find = true) {
			$c = CGAF::getClassInstance($className, $suffix, $args, $this->getAppName());
			if (!$c) {
				$c = CGAF::getClassInstance($className, $suffix, $args);
			}
			return $c;
		}
		public function getClassNameFor($base, $suffix, $ns) {
			$appName = $this->getClassPrefix();
			if ($ns[strlen($ns) - 1] === "\\") {
				$ns = substr($ns, 0, strlen($ns) - 1);
			}
			$nssearch = array(
					$ns . '\\' . $appName . $base . $suffix,
					$ns . '\\' . $base,
					'\\' . $appName . $base . $suffix,
					$ns . '\\' . $base . $suffix,
					'\\' . $base . $suffix,
					'\\' . $base);
			foreach ($nssearch as $s) {
				if (class_exists($s, false)) {
					return $s;
				}
				if (class_exists('\\' . $s, false)) {
					return '\\' . $s;
				}
			}
			return null;
		}
		/**
		 *
		 * @param $model
		 * @return System\MVC\Model
		 */
		function getModel($model, $newInstance = false) {
			if (!$newInstance && isset($this->_models[$model])) {
				$this->_models[$model]->setAppOwner($this);
				return $this->_models[$model];
			}
			CGAF::Using('Models.' . $model, true);
			$cname = $this->getClassNameFor($model, 'Model', '\\System\Models');
			if (!$cname) {
				ppd(CGAF::Using(null));
				throw new SystemException("Unable to find model " . $model);
			}
			$instance = new $cname($this);
			//$this->getClassInstance ( $model, "Model", $this );
			if (!$instance) {
				throw new SystemException("Unable to construct model " . $model);
			}
			$instance->setAppOwner($this);
			if ($newInstance) {
				return $instance;
			}
			$this->_models[$model] = $instance;
			return $this->_models[$model];
		}
		public function getInternalData($path, $create = false) {
			$iPath = Utils::ToDirectory($this->getConfig("app.internalstorage") . DS . $path);
			if (is_readable($iPath)) {
				return $iPath;
			} else {
				if ($create) {
					return Utils::makeDir($iPath);
				}
				Logger::Warning("Unable to find Internal Data $iPath");
			}
			return $iPath;
		}
		function getAuthInfo() {
			return Session::get("__logonInfo");
		}
		public function getMenuItems($position, $parent = 0, $actionPrefix = null, $showIcon = true, $loadChild = false, $includecgaf = null) {
			$model = $this->getModel("menus");
			$model->clear();
			$model->setIncludeAppId(false);
			$model->where("menu_position=" . $model->quote($position));
			$model->where("menu_state=1");
			$model->where("(menu_parent=" . $parent . ' and menu_id != ' . $parent . ')');
			$includecgaf = $includecgaf === null ? $this->getConfig('app.ui.menu.' . $position . '.includecgafui', $this->getConfig('app.ui.menu.includecgafui', true)) : $includecgaf;
			if ($includecgaf) {
				$model->where("(app_id='__cgaf' or app_id=" . $model->quote($this->getAppId()) . ")");
			} else {
				$model->where("app_id=" . $model->quote($this->getAppId()));
			}
			$model->orderBy("menu_index");
			$rows = $model->loadObjects("System\\Web\\UI\\Items\MenuItem");
			if ($rows && $loadChild) {
				foreach ($rows as $k => $r) {
					$r->setChilds($this->getMenuItems($position, $r->getId(), $actionPrefix, $showIcon, true));
				}
				//ppd($rows);
			}
			return $rows;
		}
		function getDefaultController() {
			return $this->getConfig("defaultController", "home");
		}
		protected function handleAssetRequest() {
			$ctl = $this->getController('asset');
			if ($ctl) {
				return $ctl->get();
			}
		}
		protected function handleRun() {
			$c = $this->_route["_c"];
			switch (strtolower($c)) {
			case 'uninstall':
			//return $this->uninstall();
				break;
			case 'asset':
			//	return $this->handleAssetRequest();
				break;
			case '_loc':
				$id = Request::get('id');
				if ($id) {
					$this->getLocale()->setLocale($id);
					Response::Redirect(BASE_URL);
				} else {
					$this->_route['_c'] = 'locale';
				}
				//$loc = $this->getController('locale');
				//return $loc->Index();
				break;
			case '_applist':
				$this->_route['_c'] = 'home';
				$this->_route['_a'] = 'applist';
				$this->_action = 'applist';
			}
			return false;
		}
		protected function initRun() {
			parent::initRun();
			if (Request::get("__init")) {
				Session::set("hasinit", true);
				$mode = Request::get("__js") == "true" ? true : false;
				Session::set("__jsmode", $mode);
			}
			if (Request::get('__generateManifest') == '1') {
				$this->getAppManifest(true);
			}
			Session::remove("__route");
			if (Request::isAJAXRequest()) {
				Session::set("__route", $this->getRoute());
			}
			$m = Request::get("__m");
			if ($m) {
				try {
					$m = ModuleManager::getModuleInstance($m, $this);
					$this->addSearchPath($m->getModulePath());
				} catch (AccessDeniedException $e) {
					\Logger::Warning($e->getMessage());
				}
			}
			//prevent to re add client asset
			if ($this->getRoute('_c') === 'assets') {
				$this->_route['_c'] = 'asset';
			}
			if ($this->getRoute('_c') === 'asset' && $this->getRoute('_a') === 'get') {
				Request::setDataRequest(true);
			}
		}
		function getRequestAction() {
			return $this->getRoute("_a");
		}
		protected function renderHeader() {
			if (\Request::isMobile() || !Request::isAJAXRequest()) {
				$controller = $this->getMainController();
				if ($controller) {
					return $controller->getView('header');
				} else {
					return $this->renderView('shared/header');
				}
			}
		}
		protected function handleService($serviceName) {
			return false;
		}
		protected function handleRequest() {
			$controller = null;
			try {
				$controller = $this->getController();
				if ($this->_lastError) {
					throw new SystemException($this->_lastError);
				}
			} catch (\Exception $x) {
				$this->assign('content', $x->getMessage());
			}
			if (!$controller) {
				if ($this->parent) {
					return 'no Controller';
				}
			} else {
				$this->_action = $controller->getActionAlias($this->_action);
				$this->_route["_a"] = $this->_action;
				if (method_exists($controller, $this->_action)) {
					$action = $this->_action;
				} else {
					if (Request::isDataRequest()) {
						$r = $this->handleService($this->getRoute('_c') . '-' . $this->getRoute('_a'));
						if (!$r) {
							throw new \Exception('Unhandled action ' . $this->_action . ' On Controller ' . $this->getRoute('_c'));
						}
						return $r;
					} else {
						$action = "index";
					}
				}
			}
			$content = $this->getVars("content");
			if (!$content) {
				if ($controller && $controller->isAllow($action)) {
					$params = array();
					$controller->assign($this->getVars());
					try {
						$controller->initAction($action, $params);
						$content = '';
						$cl = $controller->{$action}($params, null, null, null);
						if (!\Request::isAJAXRequest() && !\Request::isDataRequest()) {
							$content .= $controller->renderActions();
						}
						if (\Request::isDataRequest()) {
							$content = $cl;
						} else {
							$content .= \Utils::toString($cl);
						}
					} catch (\Exception $e) {
						if (!Request::isDataRequest()) {
							$content = $e->getMessage();
						} else {
							throw $e;
						}
					}
					if (!Request::isDataRequest()) {
						//convert to String
						$content = Utils::toString($content);
					}
				} elseif ($controller) {
					$content = $controller->handleAccessDenied($action);
					if (!$content) {
						$content = $controller->getLastError() ? $controller->getLastError() : "access to action $action is denied on controller " . Request::get('__c');
					}
				}
			}
			$retval = '';
			if (!Request::isDataRequest() || \Request::isMobile()) {
				$retval = $this->renderHeader();
				$retval .= $content;
			} else {
				$retval = $content;
			}
			if (\Request::isMobile() || (!Request::isAJAXRequest() && !Request::isDataRequest())) {
				if ($controller) {
					$retval .= $controller->getView('footer');
				}
			} elseif (!Request::isDataRequest()) {
				$retval .= CGAFJS::Render($this->getClientScript());
			}
			return $retval;
		}
		function Run() {
			$this->onSessionEvent(new SessionEvent(Session::getInstance(), SessionEvent::SESSION_STARTED));
			$this->initRun();
			try {
				if (!$retval = $this->handleRun()) {
					$action = $this->_action;
					$this->initRequest();
					$retval = $this->handleRequest();
				}
			} catch (\Exception $e) {
				if (Request::isJSONRequest()) {
					$retval = new JSONResult(-1, $e->getMessage());
				} else {
					$retval = $e;
				}
			}
			return $this->prepareOutput($retval);
		}
		function renderMenu($position, $controller = true, $selected = null, $class = null, $renderdiv = true) {
			if ($controller) {
				$retval = $this->getController()->renderMenu($position);
			} else {
				$items = $this->getMenuItems($position);
				$retval = "";
				if ($renderdiv)
					$retval = "<div class=\"menu-container\" id='menu-container-$position'>";
				$retval .= HTMLUtils::renderMenu($items, $selected, $class . " menu-$position", null, 'menu-' . $position);
				if ($renderdiv)
					$retval .= "</div>";
			}
			return $retval;
		}
		private function parseAction($row, $ctl, &$params) {
			$action = $row->actions;
			$raction = $ctl->getActionAlias($action);
			if ($ctl && $ctl->isAllow($action)) {
				return \URLHelper::add(APP_URL, $ctl->getControllerName() . '/' . $action, $params);
			}
		}
		function renderContents($rows, $location, $params = null, $tabmode = false) {
			if (!count($rows)) {
				return null;
			}
			$content = null;
			$menus = array();
			$controller = $this->getController()->getControllerName();
			foreach ($rows as $midx => $row) {
				$class = null;
				$dbparams = Utils::DBDataToParam($row->params, $params);
				$rparams = \Utils::arrayMerge($dbparams, $params);
				$ctl = null;
				$hcontent = null;
				$retval = null;
				$haction = null;
				/*
				 * 1 	: view handled by initAction method on controller
				 * 2 	: direct access to controller
				 * 3	: Direct link
				 * 4	: render menu
				 * 5	: direct access to controller with no title
				 */
				switch ($row->content_type) {
				case 5:
				case 2:
				//direct action to controller
					try {
						$ctl = $this->getController($row->controller);
						if ($ctl) {
							$row->actions = $ctl->getActionAlias($row->actions);
							if ($this->getConfig('content.rendercontentaction')) {
								$row->actions = $row->actions ? $row->actions : "index";
								if ($ctl->isAllow(ACLHelper::ACCESS_MANAGE)) {
									$haction[] = HTMLUtils::renderLink(URLHelper::addParam($this->getAppUrl(), array(
													'__c' => $row->controller,
													'__a' => 'aed')), __($row->controller . '.add.title', 'Add'), null, 'icons/add.png', __($row->controller . '.add.descr', 'Add Data'));
								}
							}
							if (method_exists($ctl, $row->actions) && $ctl->isAllow($row->actions)) {
								$class = $row->controller . '-' . $row->actions;
								$cparams = $rparams;
								if (isset($rparams[$row->controller])) {
									$cparams = $rparams[$row->controller];
								}
								$hcontent = $ctl->{$row->actions}($cparams);
							} elseif (!method_exists($ctl, $row->actions) && $this->isDebugMode()) {
								$hcontent = HTMLUtils::renderError('method [' . $row->actions . '] not found in class ' . $row->controller);
							}
						} else {
							$hcontent = HTMLUtils::renderError(' Controller [' . $row->controller . '] not found ');
						}
					} catch (\Exception $e) {
						if ($this->isDebugMode()) {
							$hcontent = HTMLUtils::renderError($e->getMessage());
						} else {
							continue;
						}
					}
					break;
				case 3:
					try {
						$ctl = $this->getController($row->controller);
					} catch (Exception $e) {
						$ctl = null;
					}
					$url = $this->parseAction($row, $ctl, $params);
					if ($url) {
						//cek security by internal controller
						$menus[] = HTMLUtils::renderLink($url, __($row->content_title));
					}
					break;
				case 4:
				//renderMenu
					try {
						$ctl = $this->getController($row->controller);
					} catch (Exception $e) {
						$ctl = null;
					}
					if ($ctl) {
						$hcontent = $ctl->renderMenu($row->actions);
					}
					break;
				case 1:
				default:
					try {
						if ($row->controller !== null && $this->isAllow($row->controller, "controller")) {
							$ctl = $this->getClassInstance($row->controller, "Controller", $this);
						}
					} catch (Exception $e) {
						$ctl = null;
					}
					if ($ctl !== null) {
						$hcontent = null;
						$row->__content = "";
						$action = $row->actions ? $row->actions : "index";
						$params = $row->params ? unserialize($row->params) : array();
						$params["_position"] = $location;
						//if (is_callable(array($ctl,$action))) {
						//	$hcontent = $ctl->$action($params);
						//}else
						if ($ctl->initAction($action, $params)) {
							$hcontent = $ctl->render(array(
											"_a" => $action), $params, true);
						}
					}
				}
				if ($hcontent) {
					if ($tabmode) {
						$content .= '<div id="tab-' . $midx . '">';
					}
					$content .= "<div class=\"$location-item {$row->controller} {$class} clearfix\">";
					if ((int) $row->content_type !== 5 && $this->getConfig('content.' . $controller . '.' . $location . '.header', true)) {
						$content .= "	<div class=\"ui-widget-header bar\">";
						if ($row->content_title) {
							$content .= "	<h4>" . __($row->content_title) . "</h4>";
						}
						if ($haction) {
							$content .= '<div class="action">' . HTMLUtils::render($haction) . '</div>';
						}
						$content .= "	</div>";
					}
					$content .= "<div  class=\"delim\"></div>";
					$row->__content = $hcontent;
					$rcontent = $row->__content;
					if (is_object($rcontent) && $rcontent instanceof \IRenderable) {
						$rcontent = $rcontent->render(true);
					}
					$content .= "<div class=\"content ui-widget-content\"><div>" . $rcontent . "</div></div>";
					$content .= "</div>";
					if ($tabmode) {
						$content .= '</div>';
					}
					$retOri[] = $row;
				} elseif ($menus) {
					$content = implode('', $menus);
				}
				unset($ctl);
			}
			return $content;
		}
		function renderContent($location, $controller = null, $returnori = false, $return = true, $params = null) {
			if ($controller === null) {
				$controller = $this->getController()->getControllerName();
			}
			$m = $this->getModel("content");
			$m->clear();
			$m->where("state=1");
			$m->where("(content_controller=" . $m->quote($controller) . ' or content_controller=\'__all\')');
			$m->where("position=" . $m->quote($location));
			$m->orderBy('idx');
			$rows = $m->loadAll();
			$retOri = array();
			$content = $this->renderContents($rows, $location, $params);
			$menus = array();
			if (count($menus)) {
				$c = "<div class=\"$location-item  clearfix menus\">";
				$c .= "	<div class=\"ui-widget-header bar\">";
				$c .= "		<h4>" . __('Actions') . "</h4>";
				$c .= "	</div>";
				$c .= "	<div  class=\"delim\"></div>";
				$c .= "	<div class=\"content\">";
				$c .= '		<div>';
				$c .= "	<ul>";
				foreach ($menus as $m) {
					$c .= '<li>' . $m . '</li>';
				}
				$c .= "	</ul>";
				$c .= '</div>';
				$c .= '</div></div>';
				$content = $c . $content;
			}
			$retval = null;
			if ($returnori) {
				return $retOri;
			}
			if ($content) {
				$retval = "<div class=\"content-$location\">" . $content . "</div>";
			}
			if (!$return) {
				Response::write($retval);
			}
			return $retval;
		}
		function renderControllerMenu($position = "top") {
			return $this->getController()->renderMenu($this->getController()->getRouteName() . "-$position", "menu2ndlevel");
		}
		function renderView($view, $a = null, $args = null, $controller = null) {
			$controller = $this->getController($controller);
			return $controller->getView($view, $a, $args);
		}
		function removeSession($sid) {
			if ($sid !== session_id()) {
				Session::getInstance()->destroy($sid);
				$this->onSessionEvent(new SessionEvent(null, SessionEvent::DESTROY), $sid);
				return __('session.destroyed', 'Killed');
			} else {
				return __('user.suicide', 'arrrrrrrrrrrrrrrrghhh....');
			}
		}
		function getUserInfo($id) {
			static $uinfo;
			if (isset($uinfo[$id])) {
				return $uinfo[$id];
			}
			$uinfo[$id] = new \UserInfo($this, $id);
			return $uinfo[$id];
		}
		public function handleError($ex) {
			if (Request::isJSONRequest()) {
				$json = new JSONResult(false, $ex->getMessage());
				if (class_exists('response', false)) {
					Response::write($json->Render(true));
				} else {
					echo $json->Render(true);
				}
			} elseif (Request::isAJAXRequest()) {
				ppd($ex);
			} else {
				Logger::Error("[%s] %s", get_class($ex), $ex->getMessage());
			}
		}
		function onSessionEvent(SessionEvent $event, $sid = null) {
			//ppd($_SESSION);
		}
	}
} else {
	class Application extends System\Applications\console {
	}
}
?>

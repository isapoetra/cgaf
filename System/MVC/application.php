<?php
namespace System\MVC;
use System\Web\UI\Controls\Menu;
use System\Web\JS\JSUtils;
use System\Applications\ConsoleApplication;
use System\ACL\ACLHelper;
use System\DB\DBUtil;
use System\JSON\JSONResult;
use System, CGAF, Utils;
use Request;
use Logger;
use System\Exceptions\SystemException;
use System\Exceptions\AccessDeniedException;
use System\Session\Session;
use System\Session\SessionEvent;
use System\Web\WebUtils;
use System\Applications\WebApplication;
use System\Web\Utils\HTMLUtils;
use URLHelper;
use ModuleManager;
use System\Template\TemplateHelper;
use Response;
use \System\Applications\AbstractApplication;

abstract class Application extends AbstractApplication {
	protected $_controller;
	protected $_action = "Index";
	private $_models = array();
	protected $_route = array();
	protected $_viewPath;
	protected $_searchPath = array();
	private $_messages;
	private $_ignoreJSMin = array();
	private $_userInfo = array();

	function __construct($appPath, $appName) {
		parent::__construct($appPath, $appName);
	}

	function uninstall() {	
		if (\CGAF::isAllow('manage', 'system', ACLHelper::ACCESS_MANAGE)) {
			$appId = $this->getAppId();
			$f = CGAF::getInternalStorage('db', false, true) . '/uninstall-app.sql';
			if (is_file($f)) {
				DBUtil::execScript(
					$f, CGAF::getDBConnection(), array(
					                                  'app_id' => $appId
					                             )
				);
			}
		} else {
			return false;
		}
		return parent::Uninstall();
	}

	function getControllerLookup($for) {
		$path = Utils::getDirFiles($this->getAppPath() . DS . 'Controllers' . DS);
		$retval = array();
		foreach ($path as $p) {
			$fname = Utils::getFileName($p, false);
			if ($this
				->getACL()
				->isAllow($fname, 'controller', $for)
			) {
				$retval [] = array(
					'key' => $fname,
					'value' => ucfirst($fname),
					'descr' => __('controller.' . $fname . '.descr')
				);
			}
		}
		return $retval;
	}

	public function HandleModuleNotFound($m, $u = null, $a = null) {
		$mpath = ModuleManager::getModulePath($m);
		if (!$mpath) {
			throw new AccessDeniedException ();
		}
		$this->addSearchPath($mpath);
		$f = $this->findFile("index", "Views", false);
		if ($f) {
			return TemplateHelper::renderFile($f, null, $this->getController());
		}
		return parent::handleModuleNotFound($m);
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
		if (!isset ($this->_assetCache [$type] [$prefix])) {
			if ($rprefix) {
				$search [] = $rprefix;
				$search [] = $rprefix . DS . $type;
			}
			$search [] = $type;
			$add = null;
			$image = true;
			switch (strtolower($type)) {
				case "js" :
				case "css" :
					$image = false;
				case "gif" :
				case "jpg" :
				case "png" :
				case "jpeg" :
				case "ico" :
					$search = array();
					if ($image) {
						$def = 'images';
					} else {
						$def = $prefix;
					}
					if ($type === "ico") {
						$search [] = 'images';
						$def = "icon";
					}
					$ctheme = $this->getConfig("themes", "default");
					$search [] = "themes" . DS . $ctheme . DS . $rprefix . DS . $def;
					if ($type != 'css') {
						$search [] = "themes" . DS . $ctheme . DS . $rprefix . DS . $def;
					}
					$search [] = "themes" . DS . $ctheme . DS . $def;
					$search [] = $def;
					break;
			}
			// $search = array_merge($search, array($prefix . DS . $type));
			if ($prefix !== $type) {
				$search [] = $type . DS . $prefix;
			}
			$search [] = '';
			$ap = $this->getConfig('livedatapath', 'assets');
			$retval = array();
			$spath = array(
				$this->getLivePath(false),
				$this->getAppPath(),
				CGAF_SHARED_PATH,
				SITE_PATH
			);
			foreach ($spath as $v) {
				foreach ($search as $value) {
					$retval [] = Utils::ToDirectory($v . $ap . DS . $value . DS);
				}
			}
			$this->_assetCache [$type] [$prefix] = $retval;
		}
		return $this->_assetCache [$type] [$prefix];
	}

	private function _cacheJS($f, $target, $minifymin = false) {
		$fname = $this->getAsset($f, "js");
		$content = '';
		if (!$fname) {
			// get from minified version but no source
			$fname = $this->getAsset(Utils::changeFileExt($f, 'min.js'), "js");
			if ($fname) {
				$content = file_get_contents($fname);
				if ($minifymin) {
					try {
						$content = $this->isDebugMode() ? file_get_contents($fname) : JSUtils::Pack(file_get_contents($fname));
					} catch (\Exception $e) {
						die ($e->getMessage() . ' on file ' . $fname);
					}
				}
			}
		} else {
			try {
				$content = $this->isDebugMode() ? file_get_contents($fname) : JSUtils::Pack(file_get_contents($fname));
			} catch (\Exception $e) {
				die ($e->getMessage() . ' on file ' . $fname);
			}
		}
		if ($fname) {
			return $this
				->getCacheManager()
				->putString(
				"\n" . $content, $this->isDebugMode() ? basename($fname) : $target, 'js', null,
				$this->isDebugMode() ? false : true
			);
		}
		return null;
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
			$jsname = $this
				->getCacheManager()
				->get($target, 'js');
			if ($force || !$jsname) {
				if (is_array($arr)) {
					foreach ($arr as $f) {
						$min = !in_array($f, $this->_ignoreJSMin);
						if (Utils::isLive($f)) {
							$jsname [] = $f;
						} else {
							$jsname [] = $this->_cacheJS($f, $target, $min);
						}
					}
				} else {
					$min = !in_array($arr, $this->_ignoreJSMin);
					$jsname = $this->_cacheJS($arr, $target, $min);
				}
			}
			return $this->getLiveAsset($jsname);
		}
		return $live;
	}

	private function getControllerInstance($controllerName) {
		CGAF::Using('Controller.' . $controllerName, true);
		$cname = $this->getClassNameFor($controllerName, 'Controller', 'System\\Controllers');
		if (!$cname) {
			throw new SystemException ("Unable to Find controller %s", $controllerName);
		}
		$instance = new $cname ($this);
		if (!$instance) {
			throw new SystemException ("Unable to Find controller %s", $controllerName);
		}
		return $instance;
	}

	/**
	 * @return Controller
	 * @throws \Exception
	 */
	protected function getMainController() {
		if ($this->_controller === null) {
			try {
				if (Request::get('__m')) {
					$this->_controller = \ModuleManager::getModuleInstance(Request::get('__m'));
				} else {
					$this->_controller = $this->getControllerInstance($this->getRoute('_c'));
				}
			} catch (\Exception $e) {
				if ($this->_route ['_c'] !== 'home') {
					throw $e;
				}
				$this->_route ['_c'] = 'home';
				$this->_controller = $this->getControllerInstance('home');
			}
		}
		return $this->_controller;
	}

	/**
	 * @param null $controllerName
	 * @param bool $throw
	 *
	 * @return \System\MVC\Controller
	 * @throws \Exception
	 */
	function getController($controllerName = null, $throw = true) {
		$instance = null;
		$controllerName = $controllerName ? $controllerName : Request::get('__m', $this->getRoute('_c'));
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
			return $this->_route [$arg];
		}
		return $this->_route;
	}

	/*
* function contentCallback($rpath, $content, $id, $group) { $ext =
* Utils::getFileExt ( $id, false ); if (! $ext || is_numeric ( $ext ))
* { $ext = $group; } switch (trim ( $ext )) { case "js" : $paths =
* $rpath; if (is_array ( $rpath )) { $paths = array (); foreach (
* $rpath as $v ) { if (is_array ( $v )) { if (isset ( $v ['url'] )) {
* $paths [] = $v ['url']; } } else { $paths [] = $v; } } $rpath =
* $paths; } return $this->cacheJS ( $rpath, $id ); break; case 'css' :
* if (is_array ( $rpath )) { return $this->cacheCSS ( $rpath, $id ); }
* else { return $this->cacheCSS ( $rpath, null ); } break; case 'xml' :
* if (is_file ( $id )) { $asset = $id; } else { $asset =
* $this->getAsset ( $id, $group ); } if ($asset) { $retval =
* ProjectManager::build ( $asset ); return Utils::LocalToLive (
* $retval, '' ); } else { pp ( $id ); } break; default : if (is_string
* ( $rpath ) && Utils::isLive ( $rpath )) { return $rpath; } throw new
* SystemException ( 'unhandled data type ' . $ext . " on class " .
* get_class ( $this ) . pp ( $rpath, true ) ); } return $content; }
*/
	protected function initRequest() {
		$controller = null;
		try {
			$controller = $this->getController();
		} catch (\Exception $e) {
			$this->_lastError = $e->getMessage();
		}
		$rname = $controller ? $controller->getControllerName() : 'Home';
		if (!Request::isDataRequest() && !$this->getVars('title')) {
			$title = $this->getConfig($rname . '.title', ucwords(__($rname . '.site.title', $rname)));
			$deftitle = $this->getAppId() === \CGAF::APP_ID
				? \CGAF::getConfig('cgaf.description')
				: $this->getConfig(
					'app.title', $this->getAppName()
				);
			$this->Assign('title', $this->getConfig('app.title', $deftitle) . ' ::: ' . $title);
		}
		$this->initAsset();
		$this->Assign("token", $this->getToken());
	}

	protected function initAsset() {
	}

	function isFromHome() {
		return Session::get('app.isfromhome');
	}

	function getSharedPath() {
		return dirname(__FILE__) . DS . "shared" . DS;
	}


	protected function checkInstall() {
		parent::checkInstall();
	}

	/**
	 * @param        $id
	 * @param        $group
	 * @param string $access
	 *
	 * @return bool
	 */
	function isAllow($id, $group, $access = 'view') {
		switch ($access) {
			case 'view' :
			case ACLHelper::ACCESS_VIEW :
				switch ($group) {
					case 'controller' :
						switch ($id) {
							case 'about' :
							case 'auth' :
							case 'home' :
							case 'asset' :
							case 'search' :
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
			$this->_action = $this->_route ["_a"];
			$libs = $this->getConfig('apps.libs');
			$path = $this->getAppPath();
			$this->_searchPath = array(
				$path,
				CGAF_SHARED_PATH
			);
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
		$this->dispatchEvent(new SessionEvent ($this, SessionEvent::DESTROY));
		return false;
	}

	function getMessages() {
		return $this->_messages;
	}

	/**
	 * @param $message
	 *
	 * @deprecated
	 */
	function addMessage($message) {
		if ($this->_messages == null) {
			$this->_messages = array();
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
		if (!in_array($value, $this->_searchPath)) {
			$this->_searchPath = array_merge(
				array(
				     $value
				), $this->_searchPath
			);
		}
	}

	function getSearchPath($fname, $suffix) {
		$fname = Utils::ToDirectory($fname);
		$retval = array();
		foreach ($this->_searchPath as $v) {
			$retval [] = Utils::ToDirectory($v . DS . ($suffix ? $suffix . DS : ""));
		}
		foreach ($this->_searchPath as $v) {
			$retval [] = Utils::ToDirectory($v);
		}
		return $retval;
	}

	function findFile($fname, $suffix, $throw = false) {
		// find from file
		$searchs = $this->getSearchPath($fname, $suffix);
		foreach ($searchs as $f) {
			$f = $f . $fname . CGAF_CLASS_EXT;
			if (is_file($f)) {
				if ($suffix && !\Strings::Contains($f, $suffix)) {
					continue;
				}
				return $f;
			}
		}
		if ($throw) {
			if ($this->isDebugMode()) {
				pp($fname);
				pp($suffix);
				// pp(debug_backtrace(false));
				ppd($searchs);
			}
			throw new SystemException ("error.filenotfound", $fname . ' On Class ' . get_class($this));
		}
		return null;
	}

	function getClassInstance($className, $suffix, $args, $find = true) {
		$c = CGAF::getClassInstance($className, $suffix, $args, $this->getAppName());
		if (!$c) {
			$c = CGAF::getClassInstance($className, $suffix, $args);
		}
		return $c;
	}

	/**
	 * @param      $model
	 * @param bool $newInstance
	 *
	 * @return Model
	 * @throws \System\Exceptions\SystemException
	 */
	function getModel($model, $newInstance = false) {
		if (!$newInstance && isset ($this->_models [$model])) {
			/** @noinspection PhpUndefinedMethodInspection */
			$this->_models [$model]->setAppOwner($this);
			return $this->_models [$model];
		}
		CGAF::Using('Models.' . $model, false);
		$cname = $this->getClassNameFor($model, 'Model', '\\System\Models');
		if (!$cname) {
			throw new SystemException ("Unable to find model " . $model);
		}
		/**
		 * @var Model $instance
		 */
		$instance = new $cname ($this);
		// $this->getClassInstance ( $model, "Model", $this );
		if (!$instance) {
			throw new SystemException ("Unable to construct model " . $model);
		}
		$instance->setAppOwner($this);
		if ($newInstance) {
			return $instance;
		}
		$this->_models [$model] = $instance;
		return $this->_models [$model];
	}

	public function getInternalData($path, $create = false) {
		$iPath = Utils::ToDirectory($this->getConfig("app.internalstorage") . DS . $path . DS);
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

	public function getMenuItems(
		$position, $parent = 0, $actionPrefix = null, $showIcon = true, $loadChild = false, $includecgaf = null) {
		//TODO double check (problem database field type int , param string. mysql converted to int and the result is 0, so recurive :((
		//
		if (!is_numeric($parent)) {
			return null;
		}
		$model = $this->getModel("menus");
		$model->clear();
		$model->setIncludeAppId(false);
		$model->where("menu_position=" . $model->quote($position));
		$model->where("menu_state=1");
		$model->where("(menu_parent=" . $model->quote($parent) . ' and menu_id != ' . $model->quote($parent) . ')');
		$includecgaf = $includecgaf === null ? $this->getConfig(
			'app.ui.menu.' . $position . '.includecgafui', $this->getConfig('app.ui.menu.includecgafui', true)
		) : $includecgaf;
		if ($includecgaf) {
			$model->where("(app_id='__cgaf' or app_id=" . $model->quote($this->getAppId()) . ")");
		} else {
			$model->where("app_id=" . $model->quote($this->getAppId()));
		}
		$model->orderBy("menu_index");
		$rows = $model->loadObjects("System\\Web\\UI\\Items\\MenuItem");
		if ($rows && $loadChild) {
			$r = null;
			foreach ($rows as $r) {
				/** @noinspection PhpUndefinedMethodInspection */
				$r->setChilds($this->getMenuItems($position, $r->getId(), $actionPrefix, $showIcon, true));
			}
			// ppd($rows);
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

	function isDebugMode() {
		if ($this->_isDebugMode === null) {
			$this->_isDebugMode = $this->getConfig('app.debugmode', \CGAF::isDebugMode());
			if ($this->_isDebugMode === true) {
				if (!CGAF::isDebugMode()) {
					$r = $this->getConfig('app.allowedebughost', $_SERVER ['SERVER_ADDR']);
					if ($r) {
						$r = explode(',', $r);
						$this->_isDebugMode = in_array($_SERVER ['REMOTE_ADDR'], $r);
					} else {
						$this->_isDebugMode = false;
					}
				}
			}
		}
		return $this->_isDebugMode;
	}

	public function handleError(\Exception $ex) {
		$content = $ex->getMessage();
		if ($ex instanceof AccessDeniedException) {
			if (!Request::isDataRequest()) {
				\Request::set('msg', rawurlencode($ex->getMessage()));
				\Request::set('redirect', \URLHelper::getOrigin());
				$ctl = $this->getController('auth');
				$content = $ctl->renderView('shared/header');
				$content .= $ctl->Index();
				$content .= $ctl->renderView('shared/footer');
			}
		}
		if (Request::isDataRequest()) {
			if (Request::isJSONRequest()) {
				$json = new JSONResult (false, $content);
				if (class_exists('response', false)) {
					Response::write($json->Render(true));
				} else {
					echo $json->Render(true);
				}
			} else {
				echo $content;
			}
		} else{
			echo $content;
		}
	}

	protected function handleRun() {
		$c = $this->_route ["_c"];
		switch (strtolower($c)) {
			case 'uninstall' :
				// return $this->uninstall();
				break;
			case 'asset' :
				// return $this->handleAssetRequest();
				break;
			case '_loc' :
				$id = Request::get('id');
				if ($id) {
					$this
						->getLocale()
						->setLocale($id);
					Response::Redirect(BASE_URL);
				} else {
					$this->_route ['_c'] = 'locale';
				}
				// $loc = $this->getController('locale');
				// return $loc->Index();
				break;
			case '_applist' :
				$this->_route ['_c'] = 'home';
				$this->_route ['_a'] = 'applist';
				$this->_action = 'applist';
		}
		return false;
	}

	function performCheck() {
		$rowner = \System::getCurrentUser();
		$paths = array(
			array(
				CGAF::getInternalStorage('db'),
				'0770',
				$rowner['username'],
				$rowner['groups'],
				'Internal Database Storage'),
			array(
				$this->getInternalData('.cache', true),
				'0770',
				$rowner['username'],
				$rowner['groups'],
				'Internal Cache'
			)
		);
		$error = array();
		try {
			$this->getDBConnection();
		} catch (\Exception $e) {
			$error['db']['message'] = $e->getMessage();
			$error['db']['configs'] = $this->getConfigs('db');
		}


		foreach ($paths as $p) {
			$i = new \FileInfo($p[0]);
			$perm = $i->perms;
			if ($perm['octal2'] !== $p[1]) {
				if (!\Utils::changeFileMode($p[0], $p[1])) {
					$error['paths'][] = $p;
				}
			}
		}
		return $error;
	}

	protected function initRun() {
		parent::initRun();
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
		// prevent to re add client asset
		if ($this->getRoute('_c') === 'assets') {
			$this->_route ['_c'] = 'asset';
		}
		if ($this->getRoute('_c') === 'asset' && $this->getRoute('_a') === 'get') {
			Request::setDataRequest(true);
		}
	}

	function getRequestAction() {
		return $this->getRoute("_a");
	}

	protected function renderHeader() {
		$controller = null;
		if (\Request::isMobile() || !Request::isAJAXRequest()) {
			try {
				$controller = $this->getMainController();
			} catch (\Exception $e) {

			}
			if ($controller) {
				return $controller->getView('header');
			} else {
				try {
					return $this->renderView('shared/header', null, null, 'home');
				} catch (\Exception $e) {
					return $this->renderView('shared/simpleheader');
				}
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
				throw new SystemException ($this->_lastError);
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
			$this->_route ["_a"] = $this->_action;
			if (method_exists($controller, $this->_action)) {
				$action = $this->_action;
			} else {
				if (Request::isDataRequest()) {
					$r = $this->handleService($this->getRoute('_c') . '-' . $this->getRoute('_a'));
					if (!$r) {
						throw new \Exception ('Unhandled action ' . $this->_action . ' On Controller ' . $this->getRoute('_c'));
					}
					return $r;
				} else {
					// throw new \Exception ( 'Unhandled action ' .
					// $this->_action . ' On Controller ' . $this->getRoute
					// ( '_c' ) );
					$action = "index";
				}
			}
		}
		$content = $this->getVars("content");
		if (!$content) {
			if ($controller && !$controller->isAllow($action)) {
				$content = $controller->handleAccessDenied($action);
				if (!$content) {
					$msg = ($controller->getLastError() ? $controller->getLastError() : "access to action $action is denied on controller " . Request::get('__c'));
					return $this->handleError(new AccessDeniedException($msg));
				}
			}
		}
		if (!$content) {
			$params = array();
			$controller->assign($this->getVars());
			try {
				$controller->initAction($action, $params);
				$content = '';
				$cl = $controller->{$action} ($params, null, null, null);
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
				// convert to String
				$content = Utils::toString($content);
			}

		}

		//$retval = '';
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
		}
		return $retval;
	}

	function Run() {
		$this->onSessionEvent(new SessionEvent (Session::getInstance(), SessionEvent::SESSION_STARTED));
		$this->initRun();
		try {
			if (!$retval = $this->handleRun()) {
				$action = $this->_action;
				$this->initRequest();
				$retval = $this->handleRequest();
			}
		} catch (\Exception $e) {
			return $this->handleError($e);
		}
		return $retval;
	}

	function renderMenu($position, $controller = true, $selected = null, $class = null, $renderdiv = true) {
		if ($controller) {
			$retval = $this
				->getController()
				->renderMenu($position);
		} else {
			$items = $this->getMenuItems($position);
			$retval = "";
			if ($renderdiv)
				$retval = "<div class=\"menu-container\" id='menu-container-$position'>";
			$menu = new Menu ();
			if ($position === 'menu-bar') {
				$route = $this->getRoute();
				$rname = $route ["_c"];
				$a = $route ['_a'];
				if ($items) {
					foreach ($items as $k => $row) {
						$action = $row->getAction();
						if (($row->getActionType() == 1 || $row->getActionType() == null)) {
							$action = explode('/', $action);
							if (isset ($action [1]) && $action [0] === $rname && $action [1] === $a) {
								$row->setSelected(true);
							} elseif (!isset ($action [1]) && $action [0] === $rname && $a === 'index') {
								$row->setSelected(true);
							}
						}
						$items [$k] = $row;
					}
				}
			}
			$menu->addChild($items);
			$menu->addClass($class . ' menu-' . $position);
			// $retval .= HTMLUtils::renderMenu ( $items, $selected, $class
			// . " menu-$position", null, 'menu-' . $position );
			$retval .= $menu->render(true);
			if ($renderdiv)
				$retval .= "</div>";
		}
		return $retval;
	}

	/**
	 * @param            $row
	 * @param Controller $ctl
	 * @param            $params
	 *
	 * @return mixed|string
	 */
	private function parseAction($row, Controller $ctl, &$params) {
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
		$retval = array();
		$menus = array();
		$controller = $this
			->getController()
			->getControllerName();
		foreach ($rows as $midx => $row) {
			$class = null;
			$dbparams = Utils::DBDataToParam($row->params, $params);
			$rparams = \Utils::arrayMerge($dbparams, $params);
			$ctl = null;
			$hcontent = null;
			$content = null;
			$haction = null;
			/*
* 1 	: view handled by initAction method on controller 2 	:
* direct access to controller 3	: Direct link 4	: render menu
* 5	: direct access to controller with no title
*/
			switch ($row->content_type) {
				case 5 :
				case 2 :
					// direct action to controller
					try {
						$ctl = $this->getController($row->controller);
						if ($ctl) {
							$row->actions = $ctl->getActionAlias($row->actions);
							if ($this->getConfig('content.rendercontentaction')) {
								$row->actions = $row->actions ? $row->actions : "index";
								if ($ctl->isAllow(ACLHelper::ACCESS_MANAGE)) {
									$haction [] = HTMLUtils::renderLink(
										URLHelper::addParam(
											$this->getAppUrl(), array(
											                         '__c' => $row->controller,
											                         '__a' => 'aed'
											                    )
										), __($row->controller . '.add.title', 'Add'), null, 'icons/add.png',
										__($row->controller . '.add.descr', 'Add Data')
									);
								}
							}
							if (method_exists($ctl, $row->actions) && $ctl->isAllow($row->actions)) {
								$class = $row->controller . '-' . $row->actions;
								$cparams = $rparams;
								if (isset ($rparams [$row->controller])) {
									$cparams = $rparams [$row->controller];
								}
								$hcontent = $ctl->{$row->actions} ($cparams);
							} elseif (!method_exists($ctl, $row->actions) && $this->isDebugMode()) {
								$hcontent = HTMLUtils::renderError(
									'method [' . $row->actions . '] not found in class ' . $row->controller
								);
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
				case 3 :
					try {
						$ctl = $this->getController($row->controller);
					} catch (\Exception $e) {
						$ctl = null;
					}
					$url = $this->parseAction($row, $ctl, $params);
					if ($url) {
						// cek security by internal controller
						$menus [] = HTMLUtils::renderLink($url, __($row->content_title));
					}
					break;
				case 4 :
					// renderMenu
					try {
						$ctl = $this->getController($row->controller);
					} catch (\Exception $e) {
						$ctl = null;
					}
					if ($ctl) {
						$hcontent = $ctl->renderMenu($row->actions);
					}
					break;
				case 1 :
				default :
					try {
						if ($row->controller !== null && $this->isAllow($row->controller, "controller")) {
							$ctl = $this->getClassInstance($row->controller, "Controller", $this);
						}
					} catch (\Exception $e) {
						$ctl = null;
					}
					if ($ctl !== null) {
						$hcontent = null;
						$row->__content = "";
						$action = $row->actions ? $row->actions : "index";
						$params = $row->params ? unserialize($row->params) : array();
						$params ["_position"] = $location;
						// if (is_callable(array($ctl,$action))) {
						// $hcontent = $ctl->$action($params);
						// }else
						if ($ctl->initAction($action, $params)) {
							$hcontent = $ctl->render(
								array(
								     "_a" => $action
								), $params, true
							);
						}
					}
			}
			if ($hcontent) {
				$content .= "<div class=\"$location-item {$row->controller} {$class} clearfix\">";
				if (( int )$row->content_type !== 5
					&& $this->getConfig(
						'content.' . $controller . '.' . $location . '.header', true
					)
				) {
					if ($row->content_title && !$tabmode) {
						$content .= "	<h4>" . __($row->content_title) . "</h4>";
					}
					if ($haction) {
						$content .= '<div class="action">' . HTMLUtils::render($haction) . '</div>';
					}
				}
				if (!$tabmode) {
					$content .= "<div  class=\"delim\"></div>";
				}
				$row->__content = $hcontent;
				$rcontent = $row->__content;
				if (is_object($rcontent) && $rcontent instanceof \IRenderable) {
					$rcontent = $rcontent->render(true);
				}
				$content .= "<div class=\"content ui-widget-content\"><div>" . $rcontent . "</div></div>";
				$content .= "</div>";
				$retOri [] = $row;
			} elseif ($menus) {
				$content = implode('', $menus);
			}
			if ($content) {
				$retval[$midx] = $content;
			}
			unset ($ctl);
		}
		return $retval;
	}

	function renderContent($location, $controller = null, $returnori = false, $return = true, $params = null, $tabMode = false, $appId = null) {
		if ($controller === null) {
			$controller = $this
				->getController()
				->getControllerName();
		}
		$m = $this->getModel("content");
		$m->clear();
		if ($appId) {
			$m->setIncludeAppId(false);
			$m->clear();
			$m->Where('(app_id=' . $m->quote($this->getAppId()) . ' or app_id=' . $m->quote($appId) . ')');
			$m->orderBy('app_id');
		}
		$m->where("state=1");
		$m->where("(content_controller=" . $m->quote($controller) . ' or content_controller=\'__all\')');
		$m->where("position=" . $m->quote($location));
		$m->orderBy('idx');
		$rows = $m->loadAll();
		$retOri = array();
		$content = '';
		$rcontent = $this->renderContents($rows, $location, $params, $tabMode);
		$rcontent = $rcontent ? $rcontent : array();
		if ($tabMode) {
			$content .= '<div class="tabbable">';
			$content .= '<ul class="nav nav-tabs">';
			foreach ($rows as $midx => $row) {
				if (isset($rcontent[$midx])) {
					$content
						.= '<li' . ($midx === 0 ? ' class="active"' : '') . '><a href="#tab-' . $midx . '" data-toggle="tab">' . __(
						$row->content_title
					) . '</a></li>';
				}
			}
			$content .= '</ul>';
			$content .= '<div class="tab-content">';
		}
		foreach ($rcontent as $midx => $c) {
			if ($tabMode) {
				$content .= '<div id="tab-' . $midx . '" class="tab-pane' . ($midx === 0 ? ' active' : '') . '">';
				$content .= $c;
				$content .= '</div>';
			} else {
				$content .= $c;
			}
		}
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
		if ($tabMode) {
			$content .= '</div></div>';
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
		return $this
			->getController()
			->renderMenu(
			$this
				->getController()
				->getRouteName() . "-$position", "menu2ndlevel"
		);
	}

	function renderView($view, $a = null, $args = null, $controller = null) {
		$controller = $this->getController($controller);
		return $controller->getView($view, $a, $args);
	}

	function removeSession($sid) {
		if ($sid !== session_id()) {
			Session::getInstance()
				->destroy($sid);
			$this->onSessionEvent(new SessionEvent (null, SessionEvent::DESTROY), $sid);
			return __('session.destroyed', 'Killed');
		} else {
			return __('user.suicide', 'arrrrrrrrrrrrrrrrghhh....');
		}
	}

	function getUserInfo($id) {
		if (isset ($this->_userInfo [$id])) {
			return $this->_userInfo [$id];
		}
		$this->_userInfo [$id] = new \CGAFUserInfo ($this, $id);
		return $this->_userInfo [$id];
	}

	function onSessionEvent(SessionEvent $event, $sid = null) {
		// ppd($_SESSION);
	}
}

?>

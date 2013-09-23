<?php
namespace System\MVC;

use CGAF;
use Logger;
use ModuleManager;
use Request;
use Response;
use System\ACL\ACLHelper;
use System\Applications\AbstractApplication;
use System\DB\DBQuery;
use System\DB\DBUtil;
use System\Exceptions\AccessDeniedException;
use System\Exceptions\InvalidDataTypeException;
use System\Exceptions\SystemException;
use System\JSON\JSONResult;
use System\Session\Session;
use System\Session\SessionEvent;
use System\Template\TemplateHelper;
use System\Web\JS\CGAFJS;
use System\Web\JS\JSUtils;
use System\Web\Utils\HTMLUtils;
use Utils;

abstract class Application extends AbstractApplication
{
    protected $_controller;
    protected $_action = "Index";
    private $_models = array();
    protected $_route = array();
    protected $_viewPath;
    protected $_searchPath = array();
    private $_messages;
    private $_ignoreJSMin = array();
    private $_userInfo = array();
    protected $_isDebugMode =null;
    function __construct($appPath, $appName)
    {
        parent::__construct($appPath, $appName);
    }

    function uninstall()
    {
        if ($this->isAllow('manage', 'system', ACLHelper::ACCESS_MANAGE)) {
            $appId = $this->getAppId();
            $f = CGAF::getInternalStorage('db', false, true)
                . '/uninstall-app.sql';
            if (is_file($f)) {
                DBUtil::execScript($f, CGAF::getDBConnection(),
                    array(
                        'app_id' => $appId
                    ));
            }
            $q = new DBQuery(CGAF::getDBConnection());
            $qid = $q->quote($this->getAppId());
            $q->exec('delete from #__role_privs where app_id=' . $qid);
            $q
                ->exec(
                    'delete from #__role_privs where app_id='
                    . $q->quote(\CGAF::APP_ID)
                    . ' and object_id=' . $qid
                    . ' and object_type=\'app\'');
            $q->exec('delete from #__user_roles where app_id=' . $qid);
            $q->exec('delete from #__roles where app_id=' . $qid);
            $q->exec('delete from #__menus where app_id=' . $qid);
            $q->exec('delete from #__modules where app_id=' . $qid);
            $q->exec('delete from #__lookup where app_id=' . $qid);
            $q
                ->exec(
                    'delete from #__sysvals where sysval_key_id in (select syskey_id from syskeys where app_id='
                    . $qid . ')');
            $q->exec('delete from #__syskeys where app_id=' . $qid);
            $q->exec('delete from #__recentlog where app_id=' . $qid);
            $q->exec('delete from #__contents where app_id=' . $qid);
        } else {
            return false;
        }
        return parent::Uninstall();
    }

    function getControllerLookup($for)
    {
        $path = Utils::getDirFiles(
            $this->getAppPath() . DS . 'Controllers' . DS);
        $retval = array();
        foreach ($path as $p) {
            $fname = Utils::getFileName($p, false);
            if ($this->getACL()->isAllow($fname, 'controller', $for)) {
                $retval[] = array(
                    'key' => $fname,
                    'value' => ucfirst($fname),
                    'descr' => __('controller.' . $fname . '.descr')
                );
            }
        }
        return $retval;
    }

    public function HandleModuleNotFound($m, $u = null, $a = null)
    {
        $mpath = ModuleManager::getModulePath($m);
        if (!$mpath) {
            throw new AccessDeniedException();
        }
        $this->addSearchPath($mpath);
        $f = $this->findFile("index", "Views", false);
        if ($f) {
            return TemplateHelper::renderFile($f, null, $this->getController());
        }
        /** @noinspection PhpDeprecationInspection */
        parent::handleModuleNotFound($m);
        return null;
    }

    /**
     * @param $data
     * @param null $prefix
     * @return string
     */
    function getAssetPath($data, $prefix = null)
    {
        if ($data === null) {
            return $this->getAppPath(true)
            . $this->getConfig('livedatapath', 'assets') . DS;
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
            $image = true;
            switch (strtolower($type)) {
                case "js":
                case "css":
                    $image = false;
                case "gif":
                case "jpg":
                case "png":
                case "jpeg":
                case "ico":
                    $search = array();
                    if ($image) {
                        $def = 'images';
                    } else {
                        $def = $prefix;
                    }
                    if ($type === "ico") {
                        $search[] = 'images';
                        $def = "icon";
                    }
                    $ctheme = $this->getConfig("themes", "default");
                    $search[] = "themes" . DS . $ctheme . DS . $rprefix . DS
                        . $def;
                    if ($type != 'css') {
                        $search[] = "themes" . DS . $ctheme . DS . $rprefix
                            . DS . $def;
                    }
                    $search[] = "themes" . DS . $ctheme . DS . $def;
                    $search[] = $def;
                    break;
            }
            // $search = array_merge($search, array($prefix . DS . $type));
            if ($prefix !== $type) {
                $search[] = $type . DS . $prefix;
            }
            $search[] = '';
            $ap = $this->getConfig('livedatapath', 'assets');
            $retval = array();
            $spath = array(
                $this->getLivePath(false),
                $this->getAppPath(),
                SITE_PATH
            );
            foreach ($spath as $v) {
                foreach ($search as $value) {
                    $retval[] = Utils::ToDirectory($v . $ap . DS . $value . DS);
                }
            }
            $this->_assetCache[$type][$prefix] = $retval;
        }
        return $this->_assetCache[$type][$prefix];
    }

    private function _cacheJS($f, $target, $minifymin = false)
    {
        $fname = $this->getAsset($f, "js");
        $content = '';
        if (!$fname) {
            // get from minified version but no source
            $fname = $this->getAsset(Utils::changeFileExt($f, 'min.js'), "js");
            if ($fname) {
                $content = file_get_contents($fname);
                if ($minifymin) {
                    try {
                        $content = $this->isDebugMode() ? file_get_contents(
                            $fname)
                            : JSUtils::Pack(file_get_contents($fname));
                    } catch (\Exception $e) {
                        die($e->getMessage() . ' on file ' . $fname);
                    }
                }
            }
        } else {
            try {
                $content = $this->isDebugMode() ? file_get_contents($fname)
                    : JSUtils::Pack(file_get_contents($fname));
            } catch (\Exception $e) {
                die($e->getMessage() . ' on file ' . $fname);
            }
        }
        if ($fname) {
            return $this->getCacheManager()
                ->putString("\n" . $content,
                    $this->isDebugMode() ? basename($fname) : $target,
                    'js', null, $this->isDebugMode() ? false : true);
        }
        return null;
    }

    protected function cacheJS($arr, $target, $force = false)
    {
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
            return $this->getLiveAsset($jsname);
        }
        return $live;
    }

    private function getControllerInstance($controllerName)
    {
        if (!is_string($controllerName))
            throw new InvalidDataTypeException(
                'string exprected,' . gettype($controllerName) . ' given');
        CGAF::Using('Controller.' . $controllerName, true);
        $cname = $this
            ->getClassNameFor($controllerName, 'Controller',
                'System\\Controllers');
        if (!$cname) {
            if (class_exists($controllerName)) {
                $cname = $controllerName;
            } else {
                throw new SystemException("Unable to Find controller %s",
                    $controllerName);
            }
        }
        $instance = new $cname($this);
        if (!$instance) {
            throw new SystemException("Unable to Find controller %s",
                $controllerName);
        }
        return $instance;
    }

    /**
     * @return Controller
     * @throws \Exception
     */

    protected function getMainController()
    {
        if ($this->_controller === null) {
            try {
                if (Request::get('__m')) {
                    $this->_controller = \ModuleManager::getModuleInstance(
                        Request::get('__m'));
                } else {
                    $this->_controller = $this
                        ->getControllerInstance($this->getRoute('_c'));
                }
            } catch (\Exception $e) {
                if ($this->_route['_c'] !== 'home') {
                    throw new AccessDeniedException(
                        'Invalid Application Instance or  Invalid controller '
                        . $this->getRoute('_c'));
                }
                $this->_route['_c'] = 'home';
                $this->_controller = $this->getControllerInstance('home');
            }
        }
        return $this->_controller;
    }

    /**
     * @param null $controllerName
     * @param bool $throw
     *
     * @return \System\MVC\Controller|mixed
     * @throws \Exception
     */
    function getController($controllerName = null, $throw = true)
    {
        $instance = null;
        $controllerName = $controllerName ? $controllerName
            : Request::get('__m', $this->getRoute('_c'));
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

    function initSession()
    {
        parent::initSession();
    }

    function getRoute($arg = null)
    {
        if ($arg !== null) {
            return $this->_route[$arg];
        }
        return $this->_route;
    }


    /**
     * @param $rpath
     * @param $content
     * @param $id
     * @param $group
     * @return array|mixed|null|string
     * @throws \System\Exceptions\SystemException
     * @deprecated
     */
    /*
     * function contentCallback($rpath, $content, $id, $group)
    {
        $ext =
            Utils::getFileExt($id, false);
        if (!$ext || is_numeric($ext)) {
            $ext = $group;
        }
        switch (trim($ext)) {
            case "js" :
                $paths =
                    $rpath;
                if (is_array($rpath)) {
                    $paths = array();
                    foreach (
                        $rpath as $v) {
                        if (is_array($v)) {
                            if (isset ($v ['url'])) {
                                $paths [] = $v ['url'];
                            }
                        } else {
                            $paths [] = $v;
                        }
                    }
                    $rpath =
                        $paths;
                }
                return $this->cacheJS($rpath, $id);
                break;
            case 'css' :
                if (is_array($rpath)) {
                    return $this->cacheCSS($rpath, $id);
                } else {
                    return $this->cacheCSS($rpath, null);
                }
                break;
            case 'xml' :
                if (is_file($id)) {
                    $asset = $id;
                } else {
                    $asset =
                        $this->getAsset($id, $group);
                }
                if ($asset) {
                    $retval =
                        ProjectManager::build($asset);
                    return Utils::LocalToLive(
                        $retval, '');
                } else {
                    pp($id);
                }
                break;
            default :
                if (is_string
                    ($rpath) && Utils::isLive($rpath)
                ) {
                    return $rpath;
                }
                throw new
                SystemException ('unhandled data type ' . $ext . " on class " .
                get_class($this) . pp($rpath, true));
        }
        return $content;
    }*/


    /**
     * @throws \Exception
     */
    protected function initRequest()
    {
        $controller = null;
        try {
            $controller = $this->getController();
        } catch (\Exception $e) {
            $this->_lastError = $e->getMessage();
            throw $e;
            //return;
        }
        $rname = $controller ? $controller->getControllerName() : $this->getConfig('app.defaultcontroller','home');
        if (!Request::isDataRequest() && !$this->getVars('title')) {
            $rtitle =
            $title = $this
                ->getConfig($rname . '.title',
                    __($rname.'.'.$this->getRoute('_a').'.title',
                        ucwords(__($rname . '.site.title', $rname))));

            $deftitle = $this->getAppId() === \CGAF::APP_ID ? \CGAF::getConfig(
                'cgaf.description')
                : $this->getConfig('app.title', $this->getAppName() . ' v.' . $this->getAppInfo()->app_version);
            $this
                ->Assign('title',
                    $this->getConfig('app.title', $deftitle) . ' ::: '
                    . $title);
        }
        if (!\Request::isDataRequest()) {
            $this->initAsset();
        }
        $this->Assign("token", $this->getToken());
    }

    protected function initAsset()
    {
    }

    function isFromHome()
    {
        return Session::get('app.isfromhome');
    }

    function getSharedPath()
    {
        return dirname(__FILE__) . DS . "shared" . DS;
    }

    function Reinstall()
    {
        $this->Uninstall();
        $this->Install();
        return true;
    }


    /**
     * @param $id
     * @param $group
     * @param string $access
     *
     * @param null $user
     * @return bool
     */
    function isAllow($id, $group, $access = 'view', $user = null)
    {
        switch ($access) {
            case 'view':
            case ACLHelper::ACCESS_VIEW:
                switch ($group) {
                    case 'controller':
                        switch ($id) {
                            case 'about':
                            case 'auth':
                            case  $this->getConfig('app.defaultcontroller','home'):
                            case 'asset':
                            case 'share' :
                            case 'search':
                                return true;
                                break;
                        }
                }
                break;
        }
        switch ($group) {
            case 'controller':
                if ($this
                    ->isAllow($this->getAppId(), 'app',
                        ACLHelper::ACCESS_MANAGE, $user)
                ) {
                    return true;
                }
                break;
        }
        return parent::isAllow($id, $group, $access);
    }

    public function Initialize()
    {
        if ($this->isInitialized()) {
            return true;
        }
        if (parent::Initialize()) {
            $first = \AppManager::isAppStarted() === false;
            MVCHelper::setDefaultRoute(array(
                    '_c' => $this->getConfig('app.defaultcontroller','home'),
                    '_a' => $this->getConfig('app.defaultcontrolleraction','index')
                )
            );
            $this->_route = MVCHelper::getRoute();
            $this->_action = $this->_route["_a"];
            $libs = $this->getConfig('apps.libs');
            $path = $this->getAppPath();
            $this->_searchPath[] = $path;
            $this->addSearchPath(\CGAF::getConfigs('cgaf.paths.shared'));

            CGAF::addClassPath('Controller', $path . DS . 'Controllers' . DS,$first);
            CGAF::addClassPath('Controllers', $path . DS . 'Controllers' . DS, $first);
            CGAF::addClassPath('Models', $path . DS . 'Models' . DS, $first);
            CGAF::addClassPath('Modules', $path . DS . 'Modules' . DS, $first);
            if ($libs) {
                using($libs);
            }
            if (!$first && !\AppManager::isAppStarted()) {
                $this->initRequest();
            }
            return true;
        }
        $this->dispatchEvent(new SessionEvent($this, SessionEvent::DESTROY));
        return false;
    }

    function getMessages()
    {
        return $this->_messages;
    }

    /**
     * @param $message
     *
     * @deprecated
     */
    function addMessage($message)
    {
        if ($this->_messages == null) {
            $this->_messages = array();
        }
        $this->_messages[] = $message;
    }

    protected function addSearchPath($value)
    {
        if (!$value)
            return;
        if (is_array($value)) {
            foreach ($value as $v) {
                $this->addSearchPath($v);
            }
            return;
        }
        if (!in_array($value, $this->_searchPath)) {
            $this->_searchPath[] = $value;
        }
    }

    function getSearchPath($fname, $suffix)
    {
        $fname = Utils::ToDirectory($fname);
        $retval = array();
        foreach ($this->_searchPath as $v) {
            $retval[] = Utils::ToDirectory(
                $v . DS . ($suffix ? $suffix . DS : ""));
        }
        foreach ($this->_searchPath as $v) {
            $retval[] = Utils::ToDirectory($v);
        }
        return $retval;
    }

    function findFile($fname, $suffix, $throw = false)
    {
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
            throw new SystemException("error.filenotfound",
                $fname . ' On Class ' . get_class($this));
        }
        return null;
    }

    function getClassInstance($className, $suffix, $args, $find = true,
                              $newInstance = true)
    {
        $c = CGAF::getClassInstance($className,
            $suffix ? $suffix : $this->getAppName(), $args, $newInstance);
        if (!$c) {
            $c = CGAF::getClassInstance($className, $suffix, $args,
                $newInstance);
        }
        return $c;
    }

    /**
     * @param string $model
     * @param bool $newInstance
     * @return \System\MVC\Model
     * @throws \System\Exceptions\SystemException
     */

    public function getModel($model = null, $newInstance = false)
    {
        if (!$newInstance && isset($this->_models[$model])) {
            /** @noinspection PhpUndefinedMethodInspection */
            $this->_models[$model]->setAppOwner($this);
            return $this->_models[$model];
        }
        CGAF::Using('Models.' . $model, false);
        $cname = $this->getClassNameFor($model, 'Model', '\\System\Models');
        if (!$cname) {
            throw new SystemException("Unable to find model " . $model);
        }
        /**
         * @var Model $instance
         */
        $instance = new $cname($this);
        // $this->getClassInstance ( $model, "Model", $this );
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

    public function getInternalData($path, $create = false)
    {
        $iPath = Utils::ToDirectory(
            $this->getConfig("app.internalstorage") . DS . $path . DS);
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

    function getAuthInfo()
    {
        return Session::get("__logonInfo");
    }

    public function getMenuItems($position, $parent = 0, $actionPrefix = null,
                                 $showIcon = true, $loadChild = false, $includecgaf = null,
                                 $menu_controller = null)
    {
        if (!is_numeric($parent)) {
            return null;
        }

        $model = $this->getModel("menus");
        $model->clear();
        $model->setIncludeAppId(false);
        $model->where("menu_position=" . $model->quote($position));
        $model->where("menu_state=1");
        $model
            ->where(
                "(menu_parent=" . $model->quote($parent)
                . ' and menu_id != ' . $model->quote($parent)
                . ')');
        $includecgaf = $includecgaf === null ? $this
            ->getConfig(
                'app.ui.menu.' . $position . '.includecgafui',
                $this
                    ->getConfig(
                        'app.ui.menu.includecgafui',
                        true)) : $includecgaf;
        if ($includecgaf) {
            $model
                ->where(
                    "(app_id='__cgaf' or app_id="
                    . $model->quote($this->getAppId()) . ")");
        } else {
            $model->where("app_id=" . $model->quote($this->getAppId()));
        }
        if ($menu_controller) {
            $model
                ->Where(
                    'menu_controller='
                    . $model->quote($menu_controller)
                    . ' or menu_controller is null');
        }
        $model->orderBy("menu_index");
        $rows = $model->loadObjects("System\\Web\\UI\\Items\\MenuItem");
        if ($rows && $loadChild) {
            $r = null;
            /**
             * @var \System\Web\UI\Items\MenuItem $r
             */
            foreach ($rows as $r) {
                $r
                    ->setChilds(
                        $this
                            ->getMenuItems($position, $r->getId(),
                                $actionPrefix, $showIcon, true));
            }
        }
        return $rows;
    }

    /**
     * @return array|bool|null|void
     */
    function getDefaultController()
    {
        return $this->getConfig("defaultController", "home");
    }

    /**
     * @return mixed
     */
    protected function handleAssetRequest()
    {
        $ctl = $this->getController('asset');
        if ($ctl) {
            return $ctl->get();
        }
        return null;
    }

    /**
     * @return array|bool|null|void
     */
    function isDebugMode()
    {
        if ($this->_isDebugMode === null) {
            $this->_isDebugMode = $this
                ->getConfig('app.debugmode', \CGAF::isDebugMode());
            if ($this->_isDebugMode === true) {
                if (!CGAF::isDebugMode()) {
                    $r = $this
                        ->getConfig('app.allowedebughost',
                            $_SERVER['SERVER_ADDR']);
                    if ($r) {
                        $r = explode(',', $r);
                        $this->_isDebugMode = in_array(
                            $_SERVER['REMOTE_ADDR'], $r);
                    } else {
                        $this->_isDebugMode = false;
                    }
                }
            }
        }
        return $this->_isDebugMode;
    }

    public function handleError(\Exception $ex)
    {
        $content = $ex->getMessage();
        try {
            $this->initRequest();
        } catch (\Exception $e) {
        }
        if (!Request::isDataRequest()) {
            \Response::clearBuffer();
            //\Request::set('msg', $ex->getMessage());
            \Request::set('redirect', \URLHelper::getOrigin());
            $ctl = $this->getController('auth');
            $this->addClientAsset('auth-login.css');
            $content = $ctl->renderView('shared/header');
            $this->assign('__msg', $ex->getMessage());
            $content .= $ctl->Index();
            $content .= $ctl->renderView('shared/footer');
        } else {
            if (Request::isJSONRequest()) {
                $json = new JSONResult(false, $content);
                return $json->Render(true);
            } else {
                echo $content;
            }
        }
        return $content;
    }

    protected function handleRun()
    {
        $c = $this->_route["_c"];
        switch (strtolower($c)) {
            case 'uninstall':
                // return $this->uninstall();
                break;
            case 'asset':
                // return $this->handleAssetRequest();
                break;
            case '_loc':
                $id = Request::get('id');
                if ($id) {
                    $this->getLocale()->setLocale($id);
                    Response::Redirect(BASE_URL);
                } else {
                    $this->_route['_c'] = 'locale';
                }
                // $loc = $this->getController('locale');
                // return $loc->Index();
                break;
            case '_applist':
                $this->_route['_c'] = 'home';
                $this->_route['_a'] = 'applist';
                $this->_action = 'applist';
        }
        return false;
    }

    function performCheck()
    {
        $rowner = \System::getCurrentUser();
        $paths = array(
            array(
                CGAF::getInternalStorage('db'),
                '0770',
                $rowner['username'],
                $rowner['groups'],
                'Internal Database Storage'
            ),
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

    protected function initRun()
    {
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
            $this->_route['_c'] = 'asset';
        }
        if ($this->getRoute('_c') === 'asset'
            && $this->getRoute('_a') === 'get'
        ) {
            Request::setDataRequest(true);
        }
    }

    function getRequestAction()
    {
        return $this->getRoute("_a");
    }

    protected function renderHeader()
    {
        $controller = null;
        if (\Request::isMobile() || !\Request::isAJAXRequest()) {
            try {
                $controller = $this->getMainController();
            } catch (\Exception $e) {
            }
            if ($controller) {
                return $controller->getView('header');
            } else {
                try {
                    return $this
                        ->renderView('shared/header', null, null, 'home');
                } catch (\Exception $e) {
                    return $this->renderView('shared/simpleheader');
                }
            }
        }
        return null;
    }

    /**
     * @param $serviceName
     * @return bool
     */
    protected function handleService($serviceName)
    {
        return false;
    }

    /**
     * @return array|bool|null|object|string
     * @throws \Exception
     */
    protected function handleRequest()
    {
        $controller = null;
        try {
            $controller = $this->getController();
            if ($this->_lastError) {
                throw new SystemException($this->_lastError);
            }
        } catch (\Exception $x) {
            $this->assign('content', $x->getMessage());
        }
        $action = null;
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
                    $r = $this
                        ->handleService(
                            $this->getRoute('_c') . '-'
                            . $this->getRoute('_a'));
                    if (!$r) {
                        throw new \Exception(
                            'Unhandled action ' . $this->_action
                            . ' On Controller '
                            . $this->getRoute('_c'));
                    }
                    return $r;
                } else {
                    if ($c = $controller->unhandledAction($this->_action)) {
                        $this->assign('content', $c);
                    }
                    $action = "index";
                }
            }
        }
        $content = $this->getVars("content");
        if (!$content) {
            if ($controller && !$controller->isAllow($action)) {
                $content = $controller->handleAccessDenied($action);
                if (!$content) {
                    $msg = ($controller->getLastError() ? $controller
                        ->getLastError()
                        : "access to action $action is denied on controller "
                        . Request::get('__c'));
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
                $cl = $controller->{$action}($params, null, null, null);
                if (!\Request::isAJAXRequest() && !\Request::isDataRequest()) {
                    $content .= $controller->renderActions();
                }
                if (\Request::isDataRequest()) {
                    $content = $cl;
                } else {
                    $content .= \Convert::toString($cl);
                }
            } catch (\Exception $e) {
                if (!Request::isDataRequest()) {
                    $content = $e->getMessage();
                } else {
                    throw $e;
                }
            }
            if (!Request::isDataRequest()) {
                $content = \Convert::toString($content);
            }
        }
        if (!Request::isDataRequest() || \Request::isMobile()) {
            $retval = $this->renderHeader();
            $retval .= $content;
        } else {
            $retval = $content;
        }
        if (\Request::isMobile()
            || (!Request::isAJAXRequest() && !Request::isDataRequest())
        ) {
            if ($controller) {
                $retval .= $controller->getView('footer');
            }
        }

        return $retval;
    }

    /**
     * @return array|bool|null|object|string|void
     */
    function Run()
    {
        $this
            ->onSessionEvent(
                new SessionEvent(Session::getInstance(),
                    SessionEvent::SESSION_STARTED));
        $this->initRun();
        try {
            if (!$retval = $this->handleRun()) {
                //$action = $this->_action;
                $this->initRequest();
                $retval = $this->handleRequest();
            }
        } catch (\Exception $e) {
            return $this->handleError($e);
        }
        return $retval;
    }

    /**
     * @param $position
     * @param bool $controller
     * @param null $selected
     * @param null $class
     * @param bool $renderdiv
     * @return mixed
     */
    abstract function renderMenu($position, $controller = true,
                                 $selected = null, $class = null, $renderdiv = true);

    /**
     * @param            $row
     * @param Controller $ctl
     * @param            $params
     *
     * @return mixed|string
     */

    private function parseAction($row, Controller $ctl, &$params)
    {
        $action = $row->actions;
        //$raction = $ctl->getActionAlias($action);
        if ($ctl && $ctl->isAllow($action)) {
            return \URLHelper::add(APP_URL,
                $ctl->getControllerName() . '/' . $action, $params);
        }
        return null;
    }

    /**
     * @param $row
     * @param null $params
     * @return array
     */
    function renderContentItem($row, $params = null)
    {
        $class = null;
        $dbparams = Utils::DBDataToParam($row->params, $params);
        $rparams = \Utils::arrayMerge($dbparams, $params);
        /**
         * @var \System\MVC\Controller $ctl
         */
        $ctl = null;
        $hcontent = null;
        $content = null;
        $menus = array();
        $haction = null;
        /*
         * 1 	: view handled by initAction method on controller
         * 2 	:  direct access to controller
         * 3	: Direct link
         * 4	: render menu
         * 5	: direct access to controller with no title
         * 6    : same as 5 but without div content
         */
        switch ($row->content_type) {
            case 2:
            case 5:
            case 6:
                // direct action to controller
                try {
                    //$owner = $this;
                    if ($row->controller_app) {
                        $owner = \AppManager::getInstance($row->controller_app);
                        if ($owner) {
                            $ctl = $owner->getController($row->controller);
                        } else {
                            $ctl = $this->getController($row->controller);
                        }
                    } else {
                        $ctl = $this->getController($row->controller);
                    }
                    if ($ctl) {
                        $row->actions = $ctl->getActionAlias($row->actions);
                        if ((isset($params['__renderAction']) ? $params['__renderAction']
                                : true)
                            && $this
                                ->getConfig(
                                    'content.rendercontentaction',
                                    true)
                        ) {
                            if ($this->getRoute('_c') !== $ctl->getControllerName()) {
                                if ((isset($params['__renderActionContent']) ? $params['__renderActionContent']
                                        : true) && true
                                ) {
                                    isset($params['__renderActionContent']) ? ppd(
                                        $params) : null;
                                    $ctl->initAction($row->actions, $params);
                                    $hcontent .= $ctl->renderActions(null, null, array('_a' => $row->actions));
                                }
                                try {
                                    $haction = $ctl->getAction(null);
                                } catch (\Exception $e) {
                                }
                            }
                        }
                        if (method_exists($ctl, $row->actions)
                            && $ctl->isAllow($row->actions)
                        ) {
                            //$class = $row->controller . '-' . $row->actions;
                            $cparams = $rparams;
                            if (isset($rparams[$row->controller])) {
                                $cparams = $rparams[$row->controller];
                            }
                            $ctl->initAction($row->actions, $rparams);
                            $hcontent .= \Convert::toString(
                                $ctl->{$row->actions}($cparams));
                        } elseif (!method_exists($ctl, $row->actions)
                            && $this->isDebugMode()
                        ) {
                            $hcontent .= HTMLUtils::renderError(
                                'method [' . $row->actions
                                . '] not found in class '
                                . $row->controller);
                        }
                    } else {
                        $hcontent .= HTMLUtils::renderError(
                            ' Controller [' . $row->controller
                            . '] not found ');
                    }
                } catch (\Exception $e) {
                    if ($this->isDebugMode()) {
                        $hcontent .= HTMLUtils::renderError($e->getMessage());
                    } else {
                        continue;
                    }
                }
                break;
            case 3:
                try {
                    $ctl = $this->getController($row->controller);
                } catch (\Exception $e) {
                    $ctl = null;
                }
                $url = $this->parseAction($row, $ctl, $params);
                if ($url) {
                    // cek security by internal controller
                    $menus[] = HTMLUtils::renderLink($url,
                        __($row->content_title));
                }
                break;
            case 4:
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
            case 1:
            default:
                try {
                    if ($row->controller !== null
                        && $this->isAllow($row->controller, "controller")
                    ) {
                        $ctl = $this->getControllerInstance($row->controller);

                        if (!$ctl) {
                            $c = '\\System\\Controllers\\' . $row->controller;
                            if (class_exists($c, false)) {
                                $ctl = new $c($this);
                            }
                        }
                    }
                } catch (\Exception $e) {
                    $ctl = null;
                }
                if ($ctl !== null) {
                    $hcontent = null;
                    $row->__content = "";
                    $action = $row->actions ? $row->actions : "index";
                    $params = $row->params ? unserialize($row->params) : array();
                    $params["_position"] = $row->position;
                    if ($ctl->initAction($action, $params)) {
                        $hcontent = $ctl
                            ->render(
                                array(
                                    "_a" => $action
                                ), $params, true);

                    }
                }
        }
        unset($ctl);
        return array(
            'hcontent' => $hcontent,
            'menus' => $menus,
            'actions' => $haction
        );
    }

    abstract function renderContents($rows, $location, $params = null, $tabmode = false, $controller = null);

    function getItemContents($location, $controller, $appId = null)
    {
        //$appId = $appId ? $appId  :  $this->getAppId();
        $m = $this->getModel("contents");
        $m->clear();
        $m->setIncludeAppId(false);
        $m->clear();
        if ($appId) {
            $m
                ->Where(
                    '(app_id=' . $m->quote($this->getAppId())
                    . ' or app_id=' . $m->quote($appId) . ')');
            $m->orderBy('app_id');
        } else {
            $m
                ->Where(
                    '(app_id=' . $m->quote($this->getAppId())
                    . ' or app_id=' . $m->quote(\CGAF::APP_ID)
                    . ')');
            $m->orderBy('app_id');
        }
        $m->where("state=1");
        $m
            ->where(
                "(content_controller=" . $m->quote($controller)
                . ' or content_controller=\'__all\')');
        $m->where("position=" . $m->quote($location));
        $m->orderBy('idx');
        return $m->loadObjects();
    }

    abstract function renderContent($location, $controller = null, $returnori = false,
                                    $return = true, $params = null, $tabMode = false, $appId = null);


    function renderControllerMenu($position = "top")
    {
        return $this->getController()
            ->renderMenu(
                $this->getController()->getRouteName() . "-$position",
                "menu2ndlevel");
    }

    function renderView($view, $a = null, $args = null, $controller = null)
    {
        $controller = $this->getController($controller);
        return $controller->getView($view, $a, $args);
    }

    function renderSharedView($view, $controller, $args = null)
    {
        $controller = is_object($controller) ? $controller
            : $this->getController($controller);
        if (!$controller->isAllow('view'))
            throw new AccessDeniedException();
        $f = $this->getAppPath() . 'Views/shared/'
            . $controller->getControllerName() . DS . $view . '.php';
        if (!is_file($f)) {
            $paths = \CGAF::getConfigs('cgaf.paths.shared');
            $f = null;
            foreach ($paths as $p) {
                $fname = $p . 'Views/' . $controller->getControllerName() . DS
                    . $view . '.php';
                if (is_file($fname)) {
                    $f = $fname;
                    break;
                }
            }
        }
        if (!$f) {
            throw new SystemException('View Not Found');
        }
        return TemplateHelper::renderFile($f, $args, $controller);
    }

    function removeSession($sid)
    {
        if ($sid !== session_id()) {
            Session::getInstance()->destroy($sid);
            $this
                ->onSessionEvent(
                    new SessionEvent(null, SessionEvent::DESTROY),
                    $sid);
            return __('session.destroyed', 'Killed');
        } else {
            return __('user.suicide', 'arrrrrrrrrrrrrrrrghhh....');
        }
    }

    /**
     * @param $id
     * @return mixed
     * @deprecated
     */
    function getUserInfo($id)
    {
        if (isset($this->_userInfo[$id])) {
            return $this->_userInfo[$id];
        }
        /** @noinspection PhpUndefinedClassInspection */
        $this->_userInfo[$id] = new \CGAFUserInfo($this, $id);
        return $this->_userInfo[$id];
    }

    public function notifyUser($uid, $subject, $message)
    {
        $path = \CGAF::getUserStorage($uid);
        $fInfo = $path . 'messages.bin';
        $mInfo = array();
        if (is_file($fInfo)) {
            $mInfo = unserialize(file_get_contents($fInfo));
        }
        $msgPath = $path . 'messages/';
        \Utils::makeDir($msgPath);
        if (!isset($mInfo['messages'])) {
            $mInfo['messages'] = array();
        }
        $nid = md5(time());
        $mInfo['messages'][$nid] = array(
            'flags' => 'inbox',
            'from' => ACLHelper::getUserId(),
            'subject' => $subject,
            'send' => time()
        );
        $mInfo['count'] = (isset($mInfo['count']) ? $mInfo['count'] : 0) + 1;
        $mInfo['unread'] = (isset($mInfo['unread']) ? $mInfo['unread'] : 0) + 1;
        file_put_contents($fInfo, serialize($mInfo));
        $fmessage = $msgPath . $nid . '.msg';
        $msgData = array(
            'format' => 'html',
            'message' => $message
        );
        file_put_contents($fmessage, $msgData);
    }

    public function Reset()
    {
        if (parent::Reset()) {
            \AppManager::resetAppPath($this->getAppPath());
            return true;
        }
        return false;
    }

    public function LogUserAction($action, $descr = null, $uid = null)
    {
        $m = $this->getModel('userlog');
        $m->insert('user_id', $uid === null ? ACLHelper::getUserId() : $uid);
        $m->insert('log_type', $action);
        $m->insert('log_descr', serialize($descr));
        $m->exec();
    }
}

?>
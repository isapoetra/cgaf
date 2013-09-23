<?php

namespace System\Applications;

use CGAF;
use Logger;
use Request;
use System\API\PublicApi;
use System\Exceptions\SystemException;
use System\JSON\JSON;
use System\MVC\Application;
use System\Session\Session;
use System\Web\JS\CGAFJS;
use System\Web\UI\Controls\Menu;
use System\Web\UI\Items\BreadCrumbItem;
use System\Web\UI\Items\MenuItem;
use System\Web\Utils\HTMLUtils;
use System\Web\WebUtils;
use URLHelper;
use Utils;

/**
 */
class WebApplication extends Application implements IWebApplication
{
    private $_script = '';
    private $_scripts = array();
    private $_directSript = '';
    /**
     *
     * @var \IJSEngine
     */
    private $_jsEngine;
    private $_clientScripts = array();
    private $_clientDirectScripts = array();
    private $_metas = array();
    private $_styleSheet = array();
    private $_crumbs = array();
    private $_appURL;

    /**
     * Constructor
     *
     * @param $appPath string
     * @param $appName string
     * @param bool $onlyWeb
     * @throws \System\Exceptions\SystemException
     */
    function __construct($appPath, $appName, $onlyWeb = false)
    {
        if ($onlyWeb && !\System::isWebContext()) {
            throw new SystemException ('please run from web');
        }
        parent::__construct($appPath, $appName);
    }

    /**
     * Clear Client Asset And Client Script
     */
    public function clearClient()
    {
        /*
         * static $first; if ($first === null) { $first = true; }
         */
        $this->_clientAssets->clear();
        $this->_clientScripts = array();
        $this->_clientDirectScripts = array();
        /*
         * if (!$first) { ppd('clear'); } $first = false;
         */
    }

    /**
     * @param $script mixed
     * @return mixed|void
     */
    function addClientScript($script)
    {
        if (!$script) {
            return;
        }
        if ($script===(array)$script) {
            $script = implode(CGAF_DEBUG ? PHP_EOL : '', $script);
        }
        $this->_clientScripts [] = $script;
    }

    function isFromHome()
    {
        return true;
    }

    function getAppManifest($force = false)
    {
        $f = CGAF_PATH . 'manifest/' . $this->getAppId() . '.manifest';
        // unlink($f);
        if ($force || !is_file($f)) {
            $man = $this->getConfig('app.manifest');
            Utils::generateManifest($man, $this->getAppId());
        }
        if (!$this->getConfig('site.enableoffline')) {
            return false;
        }
        return BASE_URL . 'manifest/' . basename($f);
    }

    function addClientDirectScript($script)
    {
        if (!$script) {
            return;
        }
        $this->_clientDirectScripts [] = $script;
    }

    function getStyleSheet()
    {
        return $this->_styleSheet;
    }

    function addStyleSheet($s)
    {
        if (!in_array($s, $this->_styleSheet)) {
            $this->_styleSheet [] = $s;
        }
    }

    function getClientDirectScript()
    {

        return $this->_clientDirectScripts;
    }

    function getClientScript()
    {
        return $this->_clientScripts;
    }

    public function getMetaHeader()
    {
        return $this->_metas;
    }

    public function addMetaHeader($name, $attr = null, $tag = "meta", $overwrite = false)
    {
        $rattr = array();
        $metas = null;
        if ($name===(array)$name) {
            $rattr = $name;
        } elseif (is_string($attr)) {
            if (!$attr) {
                return;
            }
            $rattr ["content"] = $attr;
        } elseif ($attr === (array)$attr || is_object($attr)) {
            $found = false;
            foreach ($attr as $k => $v) {
                if ($v === (array)$v) {
                    $this->_metas [] = array(
                        'tag' => $name,
                        'attr' => $v
                    );
                    $found = true;
                } else {
                    $rattr [$k] = $v;
                }
            }
            if ($found) {
                return;
            }
        }
        if (!$metas) {
            $metas = array(
                'tag' => $tag
            );
            if ($name && is_string($name)) {
                $metas ['name'] = $name;
            }

            if ($overwrite) {
                $nmetas = array();
                $name = isset ($metas ['name']) ? $metas ['name'] : (isset ($attr ['name']) ? $attr ['name'] : null);
                foreach ($this->_metas as $meta) {
                    if ($metas ['tag'] !== $meta ['tag'] || @$meta ['name'] !== $name) {
                        $nmetas [] = $meta;
                    }
                }
                $this->_metas = $nmetas;
            }
            $metas ['attr'] = $rattr;
            $this->_metas [] = $metas;
        }
    }

    protected function initRun()
    {
        parent::initRun();
        if (Request::get('__generateManifest') == '1') {
            $this->getAppManifest(true);
        }
        if (Request::get("__init")) {
            Session::set("hasinit", true);
            $mode = Request::get("__js") == "true" ? true : false;
            Session::set("__jsmode", $mode);
        }
    }

    function getAsset($data, $prefix = null)
    {
        if (!($data===(array)$data)) {
            $ext = strtolower(Utils::getFileExt($data, false));
            switch ($ext) {
                case 'css' :
                case 'js' :
                    $min = parent::getAsset(Utils::changeFileExt($data, 'min.' . $ext), $prefix);
                    // ppd(Utils::changeFileExt ( $data, 'min.' . $ext ));
                    if ($min && !$this->isDebugMode()) {
                        return $min;
                    }

                    $retval = parent::getAsset($data, $prefix);
                    // pp($data.'->'.$retval.'->'.Utils::changeFileExt ( $data, 'min.' . $ext ));
                    return $retval ? $retval : $min;
            }
        }
        return parent::getAsset($data, $prefix);
    }

    function renderMenu($position, $controller = true, $selected = null, $class = null, $renderdiv = true)
    {
        if ($controller) {
            $retval = $this->getController()->renderMenu($position, $class);
        } else {
            $items = $this->getMenuItems($position, 0, null, false, true);

            $retval = "";
            if ($renderdiv) {
                $retval = '<div class="menu-container" id="menu-container-' . $position . '" data-role="navbar">';
            }
            $menu = new Menu ();
            if ($this->getConfig('app.designmode', CGAF_DEBUG)) {
                $items [] = new MenuItem ('menu-design', 'Design', '/menus/manage/' . $position);
            }
            if ($position === $this->getMainMenu()) {

                Session::setState('ui', 'activemenu');
                $route = $this->getRoute();
                $rname = $route ["_c"];
                $a = $route ['_a'];
                if ($items) {
                    /**
                     * @var $row MenuItem
                     */
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
                        if ($row->getSelected()) {
                            Session::setState('ui', 'activemenu', $row->Id);
                        }

                        if ($row->hasChildMenu()) {
                            /**
                             * @var $c MenuItem
                             */
                            foreach ($row->getMenuChilds() as $c) {
                                $c->setClass('dropdown-submenu');
                                // ppd($c);
                            }
                            // ppd($row);
                        }
                        $items [$k] = $row;
                    }
                    // ppd($items);
                }
            }
            $menu->addChild($items);
            $menu->addClass($class . ' menu-' . $position);
            $retval .= $menu->render(true);
            if ($renderdiv) {
                $retval .= "</div>";
            }
        }
        return $retval;
    }

    public function renderClientAsset($mode = null)
    {
        // $retval = '';
        $retval = $this->getClientAsset()->render(true, $mode);
        return $retval;
    }

    function getAppUrl()
    {
        if (!$this->_appURL) {
            $params = array();
            if ($this->getAppId() !== \AppManager::getActiveApp()) {
                $params = array(
                    '__appId' => $this->getAppId()
                );
            }
            $capp = \AppManager::getActiveApp();
            // Cross Access Applications ??
            if ($capp && $capp !== $this->getAppId()) {
                $params = array(
                    '__appId' => $capp,
                    '__reffAppId' => $this->getAppId()
                );
            }
            if (\Request::isMobile()) {
                $params ['__mobile'] = 1;
            }
            $def = CGAF::getConfig('cgaf.defaultAppId') === $this->getAppId() ? BASE_URL : URLHelper::addParam(BASE_URL, $params);
            $this->_appURL = $this->getConfig('app.url', $def);
        }
        if (!defined('APP_URL')) {
            define ('APP_URL', $this->_appURL);
        }
        return $this->_appURL;
    }

    function Initialize()
    {
        if (parent::Initialize()) {

            $this->getAppUrl();

            /*
             * if (! defined ( 'APP_URL' )) { define ( 'APP_URL', $this->getAppUrl () ); }
             */

            CGAF::addAlowedLiveAssetPath($this->getLivePath());
            CGAF::addAlowedLiveAssetPath($this->getAppPath() . $this->getConfig('livedatapath', 'assets'));

            return true;
        }

        return false;
    }

    public function getAgentSuffix()
    {
        return Utils::getAgentSuffix();
    }

    /**
     *
     * @param
     *            $assetName
     * @return array mixed null
     */
    public function getAssetAgent($assetName)
    {
        $fname = Utils::getFileName($assetName);
        $assetAgent = Utils::changeFileName($assetName, $fname . $this->getAgentSuffix());
        return $this->getAsset($assetAgent);
    }

    function Authenticate($useToken = true)
    {
        if ($this->getConfig('auth.usecaptcha', false)) {
            if (!$this->isValidCaptcha("__captcha", true)) {
                throw new SystemException ('error.invalidcaptcha');
            }
        }
        return parent::Authenticate($useToken);
    }

    protected function isValidCaptcha($c, $a)
    {
        return true;
    }

    /**
     *
     * @return \IJSEngine
     */
    public function getJSEngine()
    {
        if (!$this->_jsEngine) {
            $c = 'System\\Web\JS\Engine\\' . $this->getConfig('js.engine', 'jQuery');
            $this->_jsEngine = new $c ($this);
            $this->_jsEngine->initialize($this);
        }
        return $this->_jsEngine;
    }

    /**
     *
     * @return array
     * @deprecated
     *
     *
     */
    protected function getDefaultTemplateParam()
    {
        return array(
            "baseurl" => BASE_URL,
            "imageLogo" => $this->getLiveAsset("logo.pg")
        );
    }

    function parseScript($m)
    {
        $match = array();
        preg_match_all('|(\w+\s*)=(\s*".*?")|', $m [0], $match);
        if (!$match [0]) {
            return $m [0];
        }
        if (!empty ($m [3])) {
            if (!in_array('ignore', $match [1])) {
                $this->_script .= $m [3];
            } else {
                $this->_directSript .= $m [3];
            }
            return '';
        }
        $val = array();
        foreach ($match [1] as $k => $v) {
            $s = $match [2] [$k];
            $s = substr($s, 1, strlen($s) - 2);
            $val [$v] = $s;
        }
        foreach ($this->_scripts as $v) {
            if (!isset ($val ['src']))
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

    protected function checkInstall()
    {
    }

    protected function prepareOutputData($s)
    {
        if (Request::isJSONRequest()) {
            header("Content-Type: application/json, text/javascript;charset=UTF-8", true);
            if (!is_string($s)) {
                if (is_object($s) && $s instanceof \IRenderable) {
                    /**
                     * @noinspection PhpUndefinedMethodInspection
                     */
                    $s = $s->Render(true);
                }
                if ($s !== null && !is_string($s)) {
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
                case 'html' :
                case 'text' :
                case '' :
                    return \Convert::toString($s);
                    break;
                default :
                    break;
            }
        }
        return $s;
    }

    function prepareOutput($s)
    {
        if (Request::isDataRequest()) {
            return $this->prepareOutputData($s);
        }
        // $c = 0;
        $stemp = $s;
        if ($this->getConfig("output.minify", false)) {
            // return $stemp;
            // using('libs.minifier.minify.min.lib.Minify.HTML');
            // return preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "",
            // $stemp);
        }
        return $stemp;
    }

    public function &getCrumbs()
    {
        return $this->_crumbs;
    }

    /**
     * Enter description here .
     *
     *
     * ..
     *
     * @param $arrCrumbs array
     */
    public function addCrumbs($arrCrumbs)
    {
        foreach ($arrCrumbs as $c) {
            $this->addCrumb($c);
        }
    }

    /**
     * Enter description here .
     *
     *
     * ..
     *
     * @param $crumb mixed
     */
    public function addCrumb($crumb)
    {
        $item = new BreadCrumbItem ();
        $item->bind($crumb);
        $this->_crumbs [] = $item;
    }

    public function clearCrumbs()
    {
        $this->_crumbs = array();
    }

    protected function handleRequest()
    {
        $retval = parent::handleRequest();
        if (Request::isAJAXRequest() && !Request::isDataRequest()) {
            /*$retval =$this->renderClientAsset('js');
            .$this->renderClientAsset('css').
                $retval;*/
            $retval .= CGAFJS::Render($this->getClientScript());

        }
        return $retval;
    }

    public function handleCommetRequest()
    {
        \Response::write("halooo");
        return true;
    }

    protected function initRequest()
    {
        ini_set('expose_php', 'off');
        header('X-UA-Compatible:IE=10');
        header('Server:');
        header_remove('pragma');
        header_remove('P3P');
        header_remove('X-Powered-By');
        // eader_remove('Cache-Control');
        // eader_remove('Last-Modified');
        $this->addMetaHeader('charset', 'utf-8');
        $this->addMetaHeader(array(
            'http-equiv' => 'Content-Type',
            'content' => 'text/html; charset=UTF-8'
        ));
        if (!\Request::isDataRequest()) {
            PublicApi::initialize($this);
            $info = $this->getAppInfo();
            $fav = $this->getLiveAsset("favicon.png");
            if ($fav) {
                $this->addMetaHeader(null, array(
                    'rel' => "shortcut icon",
                    'href' => $fav
                ), 'link');
            }
            $this->addMetaHeader(null, array(
                'rel' => "search",
                'type' => "application/opensearchdescription+xml",
                'title' => 'CGAF Search',
                'href' => BASE_URL . 'search/opensearch/?r=def'
            ), 'link');
            $this->addMetaHeader('author', $this->getConfig('app.author', 'Iwan Sapoetra'));
            $this->addMetaHeader('copyright', date('M Y'));
            $descr = $this->getConfig('app.description', $info->app_descr ? $info->app_descr : CGAF::getConfig('cgaf.description', 'CGAF'));
            $this->addMetaHeader('description', $descr);
            $this->addMetaHeader('keywords', array(
                'content' => $this->getConfig('app.keywords', CGAF::getConfig('cgaf.keywords', 'CGAF'))
            ));
            $this->addMetaHeader('Version', $info->app_version);
            $metas = $this->getConfigs('site.metas', array(
                array(
                    'name' => 'google',
                    'value' => 'notranslate'
                )
            ));
            foreach ($metas as $value) {
                $this->addMetaHeader($value);
            }
            $_crumbs = array();
            $route = $this->getRoute();
            if ($route ['_c'] !== $this->getConfig('app.defaultcontroller','home')) {
                $_crumbs [] = array(
                    'url' => $this->getAppUrl(),
                    'title' => ucwords(__('home')),
                    'class' => 'home'
                );
            }
            if ($route ['_c'] !== $this->getConfig('app.defaultcontroller','home')) {
                $_crumbs [] = array(
                    'title' => __('app.route.' . $route ['_c'] . '.title', ucwords($route ['_c'])),
                    'url' => URLHelper::add(APP_URL, $route ['_c'])
                );
            }
            if ($route ['_a'] !== $this->getConfig('app.defaultcontrolleraction','index')) {
                $_crumbs [] = array(
                    'title' => ucwords(__($route ['_c'] . '.' . $route ['_a'], $route ['_a'])),
                    'url' => URLHelper::add(APP_URL, $route ['_c'] . '/' . $route ['_a'])
                );
            }
            // Session::set('app.isfromhome', false);
            if (Session::get('app.isfromhome') == null && $route ['_c'] === $this->getConfig('app.defaultcontroller','home')) {
                Session::set('app.isfromhome', true);
            }

            if (!\Request::isDataRequest()) {
                CGAFJS::initialize($this);
            }

            $this->addCrumbs($_crumbs);
        }

        parent::initRequest();
    }

    protected function initAsset()
    {
        static $init;
        if ($init) return;
        $init = true;
        $route = $this->getRoute();
        if (!Request::isDataRequest()) {

            if ($route ['_c'] === 'asset' && $route ['_a'] === 'get')
                return;
            $maset = $this->getConfig('app.mainasset', $this->getAppPath(false));
            $this->addClientAsset($maset . '.js');
            $this->addClientAsset($maset . '.css');
            $this->addClientAsset($route ['_c'] . '-' . $route ['_a'] . '.js');
            $this->addClientAsset($route ['_c'] . '.css');
            $this->addClientAsset($route ['_c'] . '-' . $route ['_a'] . '.css');
        }
        if (!\Request::isAJAXRequest() && !\Request::isDataRequest()) {
            PublicApi::getInstance('google')->analitycs();
        }
    }

    function renderMetaHead()
    {
        $retval = '';
        foreach ($this->_metas as $value) {
            $retval .= '<' . $value ['tag'] . ' ' . (isset ($value ['name']) ? ' name="' . $value ['name'] . '" ' : ' ');
            $retval .= HTMLUtils::renderAttr($value ['attr']);
            $retval .= '/>';
        }
        return $retval;
    }

    /*
     * public function Run() { parent::Run(); $a = Request::get ( "__url" ); $a = explode ( "/", $a ); if ($a [0] == "_appList") { \Response::StartBuffer (); include Utils::ToDirectory ( $this->getSharedPath () . "Views/applist.php" ); return \Response::EndBuffer ( false ); } return false; }
     */
    protected function cacheCSS($css, $target, $force = false)
    {
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
            if ($css===(array)$css) {
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

    function assetToLive($asset, $sessionBased = false)
    {
        if ($asset===(array)$asset) {
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
        if (strpos($asset, '://') !== false || strpos($asset, '//') === 0) {
            return $asset;
        }
        if (!$this->isAllowToLive($asset)) {
            return null;
        }
        $asset = Utils::toDirectory($asset);

        if (!file_exists($asset)) {
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

        if (substr($asset, 0, strlen($apath)) === $apath) {
            $asset = \Strings::Replace($apath, '', $asset);
            return URLHelper::add($this->getAppUrl(), 'asset/' . $asset);
        }
        return CGAF::assetToLive($asset);
    }

    function renderContent($location, $controller = null, $returnori = false,
                           $return = true, $params = null, $tabMode = false, $appId = null)
    {
        $menus = array();
        if ($controller === null) {
            $controller = $this->getController()->getControllerName();
        }
        $rows = $this->getItemContents($location, $controller, $appId);
        //if ($location==='sale-detail-left') ppd($rows);
        $retOri = array();
        $content = '';
        $rcontent = $this->renderContents($rows, $location, $params, $tabMode, $controller);
        $rcontent = $rcontent ? $rcontent : array();
        if ($tabMode) {
            $content .= '<div class="tabbable">';
            $content .= '<ul class="nav nav-tabs">';
            foreach ($rows as $midx => $row) {
                if (isset($rcontent[$midx])) {
                    $content .= '<li' . ($midx === 0 ? ' class="active"' : '')
                        . '><a href="#tab-' . $midx
                        . '" data-toggle="tab">' . __($row->content_title)
                        . '</a></li>';
                }
            }
            $content .= '</ul>';
            $content .= '<div class="tab-content">';
        }
        foreach ($rcontent as $midx => $c) {
            if ($tabMode) {
                $content .= '<div id="tab-' . $midx . '" class="tab-pane'
                    . ($midx === 0 ? ' active' : '') . '">';
                $content .= $c;
                $content .= '</div>';
            } else {
                $content .= $c;
            }
        }

        if (count($menus)) {
            $c = "<div class=\"$location-item  clearfix menus\">";
            $c .= '	<div class="ui-widget-header bar">';
            $c .= '		<h4>' . __('Actions') . '</h4>';
            $c .= '	</div>';
            $c .= '	<div  class="delim"></div>';
            $c .= '	<div class="content">';
            $c .= '		<div>';
            $c .= '	<ul>';
            foreach ($menus as $m) {
                $c .= '<li>' . $m . '</li>';
            }
            $c .= '	</ul>';
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
        $div = $this->getConfig('contentcontainer.' . $location . '.renderdiv', true);
        if ($content) {
            $retval = !$div ? $content : "<div class=\"content-$location\">" . $content . "</div>";
        }
        if (!$return) {
            Response::write($retval);
        }
        return $retval;
    }

    function renderContents($rows, $location, $params = null, $tabmode = false, $controller = null)
    {
        if (!count($rows)) {
            return null;
        }
        $retval = array();
        //$menus = array();
        if (!$controller || is_string($controller)) {
            $controller = $this->getController($controller);
        }
        foreach ($rows as $midx => $row) {
            $r = $this->renderContentItem($row, $params);
            $hcontent = $r['hcontent'];
            $menus = $r['menus'];
            $content = null;
            $haction = $r['actions'];
            $class = $row->controller . '-' . $row->actions;
            if ($hcontent) {
                $div = (int)$row->content_type === 6 ? false : $this->getController($row->controller)->getConfig('contentcontainer.' . $row->position . '.renderdiv', true);
                $content .= $div ? "<div class=\"panel panel-success $location-item {$row->controller} {$class} clearfix\">" : null;
                if ((int)$row->content_type !== 6
                    && $this->getConfig('content.' . $controller->getControllerName() . '.' . $location . '.header', true)
                ) {
                    if ($row->content_title && !$tabmode && (int)$row->content_type !== 6) {
                        $content .= '<div class="panel-heading">'
                            . '<h3 class="panel-title">' . __($row->content_title) . "</h3></div>";
                    }
                    if ($haction) {
                        $content .= '<div class="action">'
                            . HTMLUtils::render($haction) . '</div>';
                    }
                }
                if (!$tabmode) {
                    $content .= !$div ? '' : '<div  class="delim"></div>';
                }
                $row->__content = $hcontent;
                $rcontent = $row->__content;
                if (is_object($rcontent) && $rcontent instanceof \IRenderable) {
                    $rcontent = $rcontent->render(true);
                }
                if ($div) {
                    $content .= '<div class="content">' . $rcontent . "</div>";
                    $content .= "</div>";
                } else {
                    $content .= $rcontent;
                }
                $retOri[] = $row;
            } elseif ($menus) {
                $content = implode('', $menus);
            }
            if ($content) {
                $retval[$midx] = $content;
            }
        }
        return $retval;
    }

    public function handleError(\Exception $ex)
    {
        $this->_crumbs = array();
        return parent::handleError($ex);
    }

    function Run()
    {
        $retval = parent::Run();
        if ($retval !== null) {
            return $this->prepareOutput($retval);
        }
        return $retval;
    }

    function cacheAssetToLive($file, $prefix, $day = 30)
    {

        $p = $this->getAssetPath('', $prefix);
        $fcache = $p[0] . '.cache.json';
        $mfname = md5($file) . \Utils::getFileExt($file, true);
        $fname = $p[0] . $mfname;
        $data = array();
        if (is_file($fcache)) {
            $data = \Convert::toArray(json_decode(file_get_contents($fcache)));
        }
        $valid = is_file($fname);
        $ds = ($day * 24 * 60 * 60 * 60);
        if (isset($data[$mfname])) {
            $valid = (time() < $data[$mfname]['m'] + $ds) && filesize($file) === $data[$mfname]['s'];
        }
        if (!$valid) {
            $data[$mfname] = array(
                'm' => filemtime($file) + $ds,
                's' => filesize($file)
            );
            \Utils::copyFile($file, $fname);
            file_put_contents($fcache, json_encode($data));
            pp($valid);
        }
        return $this->assetToLive($fname);
    }
}

?>

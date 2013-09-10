<?php

namespace System\MVC;

use CGAF;
use Logger;
use Request;
use Strings;
use System\ACL\ACLHelper;
use System\Applications\IApplication;
use System\DB\Table;
use System\Exceptions\AccessDeniedException;
use System\Exceptions\InvalidOperationException;
use System\Exceptions\SystemException;
use System\JSON\JSONResult;
use System\Session\Session;
use System\Template\TemplateHelper;
use System\Web\UI\Controls\Anchor;
use System\Web\UI\Items\MenuItem;
use System\Web\Utils\HTMLUtils;
use URLHelper;
use Utils;

/**
 */
interface IController
{

    /**
     * Enter description here .
     *
     *
     *
     *
     * ..
     *
     * @param $route mixed
     * @param $vars mixed
     * @param $contentOnly boolean
     */
    function render($route = null, $vars = null, $contentOnly = null);
}

/**
 */
abstract class Controller extends \BaseObject implements IController, \ISearchProvider
{
    /**
     *
     * @var IApplication | Application
     */
    private $_appOwner;
    // private $_viewPath;
    // private $_tpl;
    protected $_vars;
    /**
     *
     * @var string
     */
    private $_routeName;
    protected $_clientContentId = "#maincontent";
    /**
     *
     * @var Model
     */
    private $_models = array();
    protected $_initialized = false;
    protected $_warning = null;
    protected $_renderInternalAction = true;
    protected $_actionAttr = null;
    private $_currentAction;
    protected $_internalAction = array(
        'getaction',
        'isallow'
    );

    /**
     *
     * @param \System\Applications\IApplication $appOwner
     * @param null $routeName
     * @throws \System\Exceptions\SystemException
     */
    function __construct(IApplication $appOwner, $routeName = null)
    {
        $this->_appOwner = $appOwner;
        $this->_routeName = $routeName;
        if (!$this->Initialize()) {
            throw new SystemException ('unable to initialize controller %s', $this->getControllerName());
        }
    }

    public function getRenderInternalAction()
    {
        return $this->_renderInternalAction;
    }

    protected function isFromHome()
    {
        return $this->getAppOwner()->isFromHome();
    }

    /**
     *
     * @param
     *            $controller
     * @param bool $throw
     * @return Controller
     */
    protected function getController($controller, $throw = true)
    {
        if ($controller === $this->getControllerName()) {
            return $this;
        }
        return $this->getAppOwner()->getController($controller, $throw);
    }

    /**
     *
     * @param
     *            $a
     * @param null $param
     */
    protected function redirect($a, $param = null)
    {
        \Response::Redirect(\URLHelper::add(APP_URL, $this->getControllerName() . '/' . $a, $param));
    }

    /**
     * @param null $o
     * @param null $id
     * @param null $route
     * @param null $params
     * @return object|string
     */
    public function renderActions($o = null, $id = null, $route = null, $params = null)
    {
        $actions = $this->getAction($o, $id, $route, $params);
        if (!$actions) {
            return null;
        }
        return $this->render('actions', array(
            'actions' => $actions
        ), true);
    }

    protected function _isAllowAction(/** @noinspection PhpUnusedParameterInspection */
        $id, /** @noinspection PhpUnusedParameterInspection */
        $access)
    {
        return true;
    }

    protected function getRoute($route = null)
    {
        $appRoute = $this->getAppOwner()->getRoute();
        if (!$route) $route = $appRoute;
        if (is_string($route)) {
            $route = array('_c' => $route);
        }
        if (is_array($route)) {
            if (!isset($route['_c'])) {
                $route['_c'] = $this->getControllerName();
            }
            if (!isset($route['_a'])) {
                $route['_a'] = $appRoute['_a'];
            }

        }
        return $route;
    }

    /**
     * @param $o
     * @param null $id
     * @param null $route
     * @param null $params
     * @return array
     */
    public function getAction($o, $id = null, $route = null, $params = null)
    {
        $retval = array();
        $id = $id !== null ? $id : \Request::get('id');
        $appRoute = $this->getAppOwner()->getRoute();
        $route = $this->getRoute($route);

        if (!$this->_renderInternalAction || $this->getConfig('renderaction.' . $route ['_a'], true) === false)
            return $retval;
        $params = $params ? $params : array();

        $rparams = \Request::getIgnore(array(
            'CGAFSESS',
            '__url',
            '__c',
            '__a',
            'id'
        ));

        $params = array_merge($params, $rparams);
        $attrs = $this->_actionAttr;
        $controller = $this;
        if ($route['_c'] !== $this->getControllerName()) {
            $controller = $this->getController($route['_c'], false);
            if (!$controller) {
                return $retval;
            }
        }

        $c = $this->getController($route['_c']);

        if (!$c->getRenderInternalAction() || $c->getConfig('renderaction', true) === false)
            return $retval;

        $croute = $this->getAppOwner()->getRoute();
        if (is_array($route)) {
            $croute = $route;
        }
        //$url = \URLHelper::add ( APP_URL, $route );

        if ($controller->isAllow(ACLHelper::ACCESS_MANAGE)) {
            if ($o) {
                if (!$id) {
                    if ($o instanceof Table) {
                        $id = $o->getPKValue();
                    } elseif (is_object($o)) {
                        $id = $controller->getModel()->getPKValue(false, $o);
                    } else {
                        $id = Request::get('id');
                    }
                }
            } else {
                if ($appRoute ['_a'] !== 'aed' && $this->_isAllowAction($id, 'add')) {
                    $retval [] = new Anchor (\URLHelper::Add(APP_URL, $route['_c'] . '/aed', $params), __('action.add'), HTMLUtils::mergeAttr($this->_actionAttr, array(
                        'class' => 'btn btn-primary icon-plus'
                    )));
                }
                if ($this->isAllow('manage')) {
                    $retval [] = new Anchor (\URLHelper::Add(APP_URL, $route['_c'] . '/manage', $params), __('action.manage'), HTMLUtils::mergeAttr($this->_actionAttr, array(
                        'class' => 'btn-warning icon-list-alt'
                    )));
                }
            }
        }
        if ($id) {
            if ($this->isAllow('detail') && $croute ['_a'] !== 'detail') {
                $retval [] = new Anchor (\URLHelper::Add(APP_URL, $route['_c'] . '/detail', 'id=' . (is_array($id) ? implode(',', $id) : $id)), __('action.detail'), HTMLUtils::mergeAttr($this->_actionAttr, array(
                    'class' => 'btn-default icon-book'
                )));
            }
            if ($appRoute ['_c'] === $croute ['_c'] && $this->isAllow('edit') && $this->_isAllowAction($id, 'edit') && (!$id || ($croute ['_a'] !== 'aed' && $croute ['_a'] !== 'edit'))) {
                $retval [] = new Anchor (\URLHelper::Add(APP_URL, $route['_c'] . '/aed', 'id=' . $id), __('action.edit'), HTMLUtils::mergeAttr($this->_actionAttr, array(
                    'class' => 'btn-default icon-edit'
                )));
            }
            if ($appRoute ['_c'] === $croute ['_c'] && $this->isAllow('delete') && $this->_isAllowAction($id, 'delete')) {
                $retval [] = new Anchor (\URLHelper::Add(APP_URL, $route['_c'] . '/del', 'id=' . $id), __('action.delete'), HTMLUtils::mergeAttr($this->_actionAttr, array(
                    'class' => 'btn-default icon-trash'
                )));
            }
        }
        return $retval;
    }

    /**
     *
     * @param
     *            $stateName
     * @param
     *            $value
     */
    function setState($stateName, $value)
    {
        $stid = $this->getAppOwner()->getAppId() . '-' . $this->getControllerName();
        return Session::setState($stid, $stateName, $value);
    }

    /**
     *
     * @param
     *            $stateName
     * @param null $default
     */
    function getState($stateName, $default = null)
    {
        $stid = $this->getAppOwner()->getAppId() . '-' . $this->getControllerName();
        return Session::getState($stid, $stateName, $default);
    }

    /**
     *
     * @return string
     */
    function name()
    {
        return $this->_routeName;
    }

    protected function addClientAsset($asset, $group = null)
    {
        return $this->getAppOwner()->addClientAsset($asset, $group);
    }

    protected function _getControllerMenu(Model $m, $row)
    {
        $retval = array();
        $url = BASE_URL . $this->getControllerName();
        $r = MVCHelper::getRoute();
        $rid = $m->getPKValue(false, $row);
        if ($this->isAllow('view')) {
            $retval [] = array(
                'title' => 'Index',
                'url' => $url,
                'icon' => 'home-small.png'
            );
        }
        switch (strtolower($r ['_a'])) {
            case 'edit' :
            case 'aed' :
                if ($rid !== null) {
                    if ($this->isAllow(ACLHelper::ACCESS_VIEW)) {
                        $retval [] = array(
                            'title' => 'View',
                            'url' => $url . '/detail/?id=' . $rid,
                            'icon' => 'view-small.png'
                        );
                    }
                }
                break;
            default :
                if ($this->isAllow(ACLHelper::ACCESS_UPDATE)) {
                    $retval [] = array(
                        'title' => 'Edit',
                        'url' => $url . '/edit/?id=' . $rid,
                        'icon' => 'edit-small.png'
                    );
                }
                if ($this->isAllow(ACLHelper::ACCESS_MANAGE)) {
                    $retval [] = array(
                        'title' => 'Delete',
                        'url' => $url . '/del/?id=' . $rid,
                        'icon' => 'del-small.png',
                        'descr' => __('delete.confirm', 'Delete this data'),
                        'attr' => array(
                            'rel' => '#confirm'
                        )
                    );
                }
                break;
        }
        return $retval;
    }

    function renderModules($position)
    {
        return $this->renderContent($position);
    }

    protected function getLiveData($data)
    {
        return $this->getAppOwner()->getLiveAsset($data);
    }

    protected function getInternalPath($o = null, $create = true)
    {
        return $this->getAppOwner()->getInternalStorage('data/' . $this->getControllerName() . DS . $o, $create);
    }

    protected function getLivePath($o = null, $create = true)
    {
        $path = $this->getAppOwner()->getLivePath(false) . $this->getControllerName() . $o . DS;
        if (!is_dir($path) && $create) {
            return \Utils::makeDir($path);
        }
        return $path;
    }

    protected function setModel($model)
    {
        if (is_string($model)) {
            $model = $this->getAppOwner()->getModel($model);
        }
        $this->_models [$this->getControllerName()] = $model;
    }

    public function getConfig($configName, $def = null)
    {
        //pp($this->getControllerName () . '.' . $configName);
        return $this->getAppOwner()->getConfig($this->getControllerName() . '.' . $configName, $def);
    }

    protected function getUserConfig($configName, $def = null)
    {
        return $this->getAppOwner()->getUserConfig($this->getControllerName() . '.' . $configName, $def);
    }

    function _changeApp()
    {
    }

    protected function getManageAction()
    {
        if ($this->_currentAction == null) {
            $this->_currentAction = strtolower(Request::get("_gridAction", Request::get("action", Request::get("__a", Request::get("oper")))));
        }
        return $this->_currentAction;
    }

    protected function getWarningText($li = true)
    {
        if (!$this->_warning) {
            return '';
        }
        $retval = $li ? '<ul class="warning">' : '';
        foreach ($this->_warning as $w) {
            $retval .= $li ? '<li>' . $w . '</li>' : "$w\n";
        }
        $retval .= $li ? '</ul>' : '';
        return $retval;
    }

    protected function getIdFromRequest()
    {
        $id = Request::get('id', isset ($args ['id']) ? $args ['id'] : null);
        if ($id == null) {
            $url = URLHelper::parseURL(trim($_REQUEST ['__url'], '/ '))['path'];
            $id = substr($url, strrpos($url, '/') + 1);
            $id = $id == 0 ? null : $id;
        }
        return $id;
    }

    function detail($args = null, $return = null)
    {
        $args = $args ? $args : array();
        $id = Request::get('id', isset ($args ['id']) ? $args ['id'] : null);
        if ($id == null) {
            $id = URLHelper::getIdFromRequest();
            $id = $id == 0 ? null : $id;
        }
        $m = $this->getModel();
        if (!$m) {
            throw new SystemException ('unable to get model on controller' . $this->getControllerName());
        }
        if ($id !== null) {
            $row = $m->reset('detail', $id)->whereId($id)->loadObject();
            $args ['row'] = $row;
        } else {
            throw new InvalidOperationException ('Invalid ID');
        }
        $menus = $this->renderControllerMenu($m, $row);
        $args = array_merge(array(
            'menus' => $menus
        ), $args);
        return $this->render(array(
            '_a' => isset($args['__view']) ? $args['__view'] : __FUNCTION__
        ), $args, $return);
    }

    function undel($id = null)
    {
        if (!$this->isAllow(ACLHelper::ACCESS_MANAGE)) {
            throw new AccessDeniedException ();
        }
        if (is_array($id) && !count($id)) {
            $id = null;
        }
        $id = $id !== null ? $id : Request::get('id');
        if (!$id) {
            throw new InvalidOperationException ('Invalid Id');
        }
        $m = $this->getModel();
        $m->setPKValue($id);
        $m->whereId($id);
        if ($m->undel()) {
            return new JSONResult (true, 'data.restored');
        } else {
            return new JSONResult (false, $m->getLastError());
        }
    }

    function del($id = null)
    {
        if (!$this->isAllow(ACLHelper::ACCESS_MANAGE)) {
            throw new AccessDeniedException ();
        }
        if (is_array($id) && !count($id)) {
            $id = null;
        }
        $id = $id !== null ? $id : Request::get('id');
        if (!$id) {
            throw new InvalidOperationException ('Invalid Id');
        }
        $m = $this->getModel();
        $m->setPKValue($id);
        $m->whereId($id);
        if ($m->delete()) {
            return new JSONResult (true, 'data.removed');
        } else {
            return new JSONResult (false, $m->getLastError());
        }
    }

    function store()
    {
        $m = $this->getModel();
        if (!$m) {
            throw new SystemException ('Invalid Model');
        }
        //$retval = new JSONResult ( false, 'data.storefailed' );
        if ($this->getAppOwner()->isValidToken()) {
            $m->bind(Request::gets(null));
            $warning = $this->getWarningText();
            $warning = $warning ? array(
                'content' => $warning
            ) : '';
            try {
                if ($m->store()) {
                    $msg = __('data.stored');
                    if ($this->isAllow('detail')) {
                        $msg .= '<br/>Click <a href="' . BASE_URL . $this->getControllerName() . '/detail/?id=' . $m->getPKValue(false, $m) . '">Here</a> to view data';
                    }
                    $retval = new JSONResult (true, $msg, null, $warning);
                } else {
                    $retval = new JSONResult (false, $m->getLastError(), null, $warning);
                }
            } catch (\Exception $e) {
                $retval = new JSONResult (false, $e->getMessage(), null, $warning);
            }
        } else {
            throw new SystemException ('Invalid Token');
        }
        return $retval; // ;
    }

    function edit($row = null)
    {
        return $this->aed($row);
    }

    function add()
    {
        return $this->aed();
    }

    function aed($row = null, $action = 'aed', $args = array())
    {
        if ($this->getAppOwner()->isValidToken()) {
            return $this->store();
        }
        $action = $action ? $action : 'aed';
        $m = $this->getModel();
        if (!$m) {
            throw new SystemException ('Invalid Model for controller ' . $this->getControllerName());
        }
        if (is_object($row)) {
            $id = $this->getModel()->getPKValue(false, $row);
        } elseif ($row !== null && !is_array($row)) {
            $id = $row;
        } else {
            $id = Request::get('id');
        }
        $allow = $this->isAllow($id ? ACLHelper::ACCESS_UPDATE : ACLHelper::ACCESS_WRITE);
        if (!$allow) {
            throw new AccessDeniedException ();
        }
        $row = $id !== null ? $m->load($id) : $m;
        if (!$row && $id !== null) {
            throw new InvalidOperationException ('Editing data with ID ' . $id . ' not allowed by system');
        }
        if (!$id) {
            $m->bind(\Request::gets());
        }
        $def = array(
            'id' => $id,
            'editForm' => 'add_edit',
            'msg' => null,
            'editmode' => $id !== null,
            'formAction' => URLHelper::addParam(APP_URL, '__c=' . $this->getControllerName() . '&__a=store&id=' . $id),
            'formAttr' => array(
                'id' => 'frm-aed-' . $this->getControllerName()
            ),
            'controller' => $this,
            'row' => $row,
            'menus' => $this->renderControllerMenu($m, $row)
        );
        $args = \Utils::arrayMerge($def, $args);
        // ppd($action);
        return $this->render(array(
            '_a' => $action
        ), $args);
    }

    protected function renderControllerMenu($m, $row)
    {
        $items = $this->_getControllerMenu($m, $row);
        return HTMLUtils::renderLinks($items, array(
            'class' => 'controller-menu'
        ));
    }

    function manage($vars = null, $newroute = null, $return = false)
    {
        $action = $this->getManageAction();
        // ppd($action);
        $vars = $vars ? $vars : array();
        if (!isset ($vars ['title'])) {
            $vars ['title'] = __($this->getControllerName() . '.' . $action . '.title', ucwords($action . ' ' . $this->getControllerName()));
        }
        $row = $this->getModel();
        if ($row) {
            $row->reset();
        }
        if ($action && !isset ($newroute ['_a'])) {
            $newroute ["_a"] = $action;
        }
        $this->Assign($vars, null);
        switch ($action) {
            case "edit" :
                return $this->edit();
                break;
            case "store" :
                return $this->store();
                break;
            case "detail" :
                break;
            case "add" :
                $retval = $this->add();
                if ($retval) {
                    return $retval;
                }
                break;
            case 'del' :
            case "delete" :
                $this->Assign($vars);
                return $this->del();
                break;
            default :
                $vars ['openGridEditInOverlay'] = isset ($vars ['openGridEditInOverlay']) ? $vars ['openGridEditInOverlay'] : false;
                if (!isset ($vars ['gridConfigs'])) {
                    $vars ['gridConfigs'] = array(
                        'addurl' => \URLHelper::add(APP_URL, $this->getControllerName() . '/aed/')
                    );
                }
        }
        if (!isset ($vars ['row'])) {
            $vars ['row'] = $row;
        }
        return $this->render($newroute, $vars, $return);
    }

    function getItemContents($position)
    {
        return $this->getAppOwner()->getItemContents($position, $this->getControllerName());
    }

    function renderContent($position, $params = null, $tabMode = false, $appId = null)
    {
        return $this->getAppOwner()->renderContent($position, $this->getControllerName(), false, true, $params, $tabMode, $appId);
    }

    function isAllowItem($itemid, $access = "view")
    {
        return $this->getAppOwner()->getACL()->isAllow($itemid, $this->getControllerName(), $access);
    }

    public function handleAccessDenied(/** @noinspection PhpUnusedParameterInspection */
        $action)
    {
        return false;
    }

    /**
     * check for security
     *
     * @param $access string
     * @return bool
     */
    public function isAllow($access = 'view')
    {
        switch (strtolower($access)) {

            case ACLHelper::ACCESS_VIEW :
            case 'view' :
            case 'applist' :
            case 'index' :
            case 'menu' :
            case 'search' :
            case 'detail' :
                $access = 'view';
                break;
            case 'store' :
                $access = ACLHelper::ACCESS_WRITE | ACLHelper::ACCESS_UPDATE;
                break;
            case 'manage' :
                $access = ACLHelper::ACCESS_MANAGE;
                break;
            default :
                if (in_array($access, $this->_internalAction)) {
                    return false;
                }
                break;
        }
        $retval = $this->getAppOwner()->isAllow($this->getControllerName(), "controller", $access);
        if ($access !== 'view' && $this->getAppOwner()->getConfig('app.security.forcefromhome', true)) {
            $retval = $retval && (Session::get('fromhome') || $this->getAppOwner()->isAuthentificated());
        }
        return $retval;
    }

    public function checkAccess($access)
    {
        $allow = $this->isAllow($access);
        if (!$allow) {
            if (Request::isAJAXRequest()) {
                throw new AccessDeniedException ("Access $access denied. to Controller " . $this->getControllerName());
            } else {
                \Response::RedirectToLogin("Access $access denied. to Controller " . $this->getControllerName());
            }
        }
        return true;
    }

    protected function prepareSearchResult(&$rows)
    {

    }

    function search($s = null, $config = null)
    {
        $s = $s ? $s : \Request::get('q');
        $row = \Request::gets('p', true, false, false);
        if ($s && $m = $this->getModel()) {
            $rows = $m->search($s, isset ($config ['field']) ? $config ['field'] : null, $config);
            $this->prepareSearchResult($rows);
            $retval = '';
            if ($this->getConfig('search.refine', true)) {
                $retval = $this->renderView(__FUNCTION__, array('q' => $s));
            }
            $retval .= $this->renderView('search-result', array(
                'rows' => $rows,
                'row' => $row
            ));
            return $retval;
        }
        return $this->renderView(__FUNCTION__, array('q' => $s));

    }

    function getDbConnection()
    {
        return $this->getAppOwner()->getDBConnection();
    }

    function getControllerName()
    {
        if ($this->_routeName == null) {
            $cl = Utils::removeNameSpace(get_class($this));
            if (Strings::BeginWith($cl, CGAF_CLASS_PREFIX)) {
                $r = str_ireplace("Controller", "", substr($cl, strlen(CGAF_CLASS_PREFIX)));
            } else {
                $r = str_ireplace("Controller", "", $cl);
            }
            $cp = $this->getAppOwner()->getClassPrefix();
            if (substr($r, 0, strlen($cp)) === $cp) {
                $r = substr($r, strlen($cp));
            }
            $this->_routeName = strtolower($r);
        }
        return $this->_routeName;
    }

    /**
     * Enter description here .
     *
     *
     *
     *
     * ..
     *
     * @deprecated please use getControllerName
     * @return string
     */
    function getRouteName()
    {
        return $this->getControllerName();
    }

    /**
     *
     * @return \System\ACL\IACL
     */
    protected function getACL()
    {
        return $this->getAppOwner()->getACL();
    }

    protected function getManageMenu()
    {
        $items = $this->getMenuItems('manage', 0, '/manage', true);
        $acl = $this->getACL();
        $manage = array();
        foreach ($items as $v) {
            /** @noinspection PhpUndefinedMethodInspection */
            $act = ($v instanceof MenuItem ? ($v->real_menuaction ? $v->real_menuaction : $v->getMenuAction()) : $v->menu_action);
            if ($acl->isAllow($act, 'manage')) {
                $manage [] = $v;
            }
        }
        return $manage;
    }

    public function getMenuItems($position, $parent = 0, $actionPrefix = null, $showIcon = true)
    {
        $rows = $this->getAppOwner()->getMenuItems($position, $parent, $actionPrefix, $showIcon, false, false, $this->getControllerName());
        // ppd('app.ui.menu.'.$position.'.loadchild');
        // $loadChild = $this->getAppOwner()->getConfig('app.ui.menu.'.$position.'.loadchild',true);
        $filtered = array();
        if (count($rows)) {
            $route = $this->getAppOwner()->getRoute();
            $rname = $route ["_c"];
            $a = $route ['_a'];
            /**
             *
             * @var $row MenuItem
             */
            foreach ($rows as $row) {
                $action = $row->getAction();
                $row->real_menuaction = $action;
                if ($actionPrefix) {
                    $row->setAction($row->getAction() . $actionPrefix);
                }
                $row->setShowIcon($showIcon);
                if (($row->getActionType() == 1 || $row->getActionType() == null)) {
                    $action = explode('/', $action);
                    if (isset ($action [1]) && $action [0] === $rname && $action [1] === $a) {
                        $row->setSelected(true);
                    } elseif (!isset ($action [1]) && $action [0] === $rname && $a === 'index') {
                        $row->setSelected(true);
                    }
                }
                if ($position == $this->getAppOwner()->getMainMenu()) {
                    if ($row->getSelected()) {
                        Session::setState('ui', 'activemenu', $row->Id);
                    }
                }
                $row->childs = array();
                if ($row->getId() !== $parent) {
                    $row->childs = $this->getMenuItems($position, $row->getId(), $actionPrefix, $showIcon);
                }
                $filtered [] = $row;
            }
        }
        return $filtered;
    }

    function menu($return = false, $params)
    {
        $params = $params ? $params : array();
        $params ['position'] = isset ($params ['position']) ? $params ['position'] : $this->getControllerName();
        $params ['style'] = isset ($params ['style']) ? $params ['position'] : $this->getControllerName() . '-menu';
        return $this->renderMenu($params ['position'], $params ['style']);
    }

    function renderMenu($position, $ulclass = null, $showIcon = false, $replacer = array(), $parent = 0)
    {
        if (is_array($position)) {
            $filtered = $position;
            $position = "custom";
        } else {
            $filtered = $this->getMenuItems($position, $parent, null, $showIcon);
        }

        if ($position == 'menu-bar' && !\Request::isMobile()) {
            $r = $this->getAppOwner()->getRoute();
            $home = new MenuItem ('home', 'Home', APP_URL, $r ['_c'] === $this->getAppOwner()->getDefaultController(), 'home.descr');
            $home->setShowIcon(true);
            $home->setIcon('appicon.png');
            $home->setClass('home');
            $filtered = array_merge(array(
                $home
            ), $filtered);
            /**
             * @var MenuItem $row
             */
            foreach ($filtered as $row) {
                if ($row->hasChildMenu()) {
                    /**
                     * @var MenuItem $c
                     */
                    foreach ($row->getMenuChilds() as $c) {
                        $c->setClass('dropdown-submenu');
                    }
                }
            }
        }
        $retval = "<div class=\"menu-container\" id='menu-container-$position'>";
        if (!count($filtered) && $position != "menu-bar") {
            if (CGAF_DEBUG) {
                $retval .= "<div class=\"warning\">Menu not found for position $position @" . $this->getControllerName();
                $retval .= " app : " . $this->getAppOwner()->getAppId() . ' Controller ' . $this->getRouteName() . '</div>';
            }
        }

        $retval .= HTMLUtils::renderMenu($filtered, null, $ulclass . " menu-$position", $replacer, 'menu-' . $position);
        $retval .= "</div>";
        return $retval;
    }

    protected function Initialize()
    {
        if ($this->_initialized) {
            return true;
        }
        // $this->getAppOwner()->getRequestAction()
        if (!$this->isAllow()) {
            throw new AccessDeniedException ("Access denied to Controller %s", $this->getControllerName());
            // return false;
        }
        try {
            if ($this->getControllerName() !== 'home') {
                if (!$this->_model) {
                    $this->setModel($this->getControllerName());
                }
            }
        } catch (\Exception $e) {
        }
        $this->Assign("baseurl", BASE_URL);
        $this->Assign("content", null);
        return true;
    }

    /**
     * get Application Owner
     *
     * @return \System\MVC\Application
     */
    function getAppOwner()
    {
        return $this->_appOwner;
    }

    function Assign($var, $val = null)
    {
        if (is_array($var) && $val == null) {
            foreach ($var as $k => $v) {
                if ($v === null) {
                    unset ($this->_vars [$k]);
                } else {
                    $this->_vars [$k] = $v;
                }
            }
            return $this;
        }
        if ($val === null) {
            unset ($this->_vars [$var]);
        } else {
            $this->_vars [$var] = $val;
        }
        return $this;
    }

    protected function getVars($varname = null, $default = null)
    {
        if ($varname === null) {
            return $this->_vars;
        }
        return isset ($this->_vars [$varname]) ? $this->_vars [$varname] : $default;
    }

    protected function getVar($name)
    {
        return isset ($this->_vars [$name]) ? $this->_vars [$name] : null;
    }

    function Index()
    {
        return $this->render('index', $this->_vars);
    }

    function getClassInstance($className, $suffix, $args = null)
    {
        return $this->getAppOwner()->getClassInstance($className, $suffix, $args);
    }

    /**
     *
     * @param string $modelName
     * @return \System\MVC\Model| mixed
     */
    function getModel($modelName = null)
    {
        if ($modelName == null) {
            $modelName = $this->getControllerName();
        }
        if (isset ($this->_models [$modelName])) {
            return $this->_models [$modelName];
        } else {
            $this->_models [$modelName] = $this->getAppOwner()->getModel($modelName);
        }
        return $this->_models [$modelName];
    }

    function getFile($viewName, $a, $prefix, $forceThrow = true)
    {
        if ($a == null) {
            $route = $this->getControllerName();
            $x = $viewName;
            $viewName = $route;
            $a = $x;
        }
        $f = $this->getAppOwner()->findFile($a, $prefix . DS . strtolower($viewName));
        if ($f == null) {
            $f = $this->getAppOwner()->findFile($a, $prefix . DS . "shared");
            if ($f == null) {
                $f = $this->getAppOwner()->findFile($a, $prefix . DS);
                if ($f == null) {
                    $f = $this->getAppOwner()->findFile($a, $prefix . DS . $this->getAppOwner()->getDefaultController(), $forceThrow);
                    if ($f == null) {
                    }
                }
            }
        }
        return $f;
    }

    public function renderView($viewName, $params = array(), $contentOnly = true)
    {
        if (!isset ($params ['controller'])) {
            $params ['controller'] = $this;
        }
        return $this->render(array(
            '_c' => $this->getControllerName(),
            '_a' => $viewName
        ), $params, $contentOnly);
    }

    public function getView($viewName, $a = null, $attr = null, $classOnly = false)
    {
        $c = null;
        try {
            if ($a) {
                $c = $this->getClassInstance($viewName . $a, 'View', $this);
            }
            if (!$c) {
                $c = $this->getClassInstance($viewName, 'View', $this);
            }
        } catch (\Exception $ex) {
            \Logger::Error($ex);
        }
        if ($c) {
            return $c;
        } elseif ($classOnly) {
            return null;
        }
        try {
            $f = $this->getFile(strtolower($viewName), $a, 'Views', false);
            if ($f == null) {
                $f = $this->getFile($viewName, 'index', 'Views', false);
            }
            if (!$f) {
                \Logger::debug('View Class Not found %s', $viewName);
            }
            return TemplateHelper::renderFile($f, $attr, $this);
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    function preRender($route, $contentOnly = false)
    {
        if (!$contentOnly) {
            // $r = $this->getAppOwner()->getRoute();//::get ( "__route" );
            // if ($r !== null && $route ["_c"] != $r ["_c"] && $route ["_a"] !=
            // $r ["_a"]) {
            // $content = $this->getView ( $r ["_c"], $r ["_a"] );
            // $this->Assign ( "content", $content );
            // }
        }
    }

    function initAction($action, &$params)
    {
        if (!Request::isDataRequest()) {
            $this->getAppOwner()->addClientAsset($this->getControllerName() . '.css');
            $this->getAppOwner()->addClientAsset($this->getControllerName() . '-'.$this->getRoute()['_a'].'.css');
            $this->getAppOwner()->addClientAsset($this->getControllerName() . '.js');
        }
        if (is_array($params)) {
            foreach ($params as $k => $v) {
                $this->_vars [$k] = $v;
            }
        }
        return true;
    }

    protected function renderContentOnly()
    {
        return null;
    }

    function render($route = null, $vars = null, $contentOnly = null)
    {
        $retval = '';
        if ($contentOnly == null) {
            $contentOnly = Request::isAJAXRequest() || Request::isDataRequest();
        }
        if ($this->getAppOwner()->getParent()) {
            $contentOnly = true;
        }
        $vars = $vars == null ? $this->getVars() : $vars;
        if (!is_array($vars)) {
            ppd($vars);
        }
        $vars = array_merge($this->_vars, $vars);
        $approute = $this->_appOwner->getRoute();
        if (is_string($route)) {
            $route = array(
                '_a' => $route
            );
        }
        if ($route == null || !is_array($route)) {
            $route = array();
        }
        $route ["_c"] = isset ($route ["_c"]) ? $route ["_c"] : $this->getControllerName();
        $route = array_merge($approute, $route);
        $this->preRender($route, $contentOnly);
        $route ["_a"] = strtolower($route ["_a"]);
        $content = isset ($vars ['content']) ? $vars ['content'] : '';
        if ($this->getControllerName() !== $route ['_c']) {
            $ctl = $this->getAppOwner()->getController($route ["_c"]);
            return $ctl->render(array(
                '_a' => $route ['_a']
            ), $vars, $contentOnly);
        }
        $this->initAction($route ['_a'], $vars);
        if (!$content) {
            $content = $this->getView($route ["_c"], $route ["_a"], $vars);
        }
        $vars ["content"] = \Convert::toString($content);
        // $this->Assign("content", $vars["content"]);
        if ($contentOnly) {
            return $vars ["content"];
        } else {
            $retval = $vars ["content"];
        }
        return $retval;
    }

    public function getActionAlias($action)
    {
        return $action;
    }

    public function prepareRender()
    {
    }

    public function handleResult($params)
    {
        if (Request::isJSONRequest()) {
            return new JSONResult (1, '', null, $params);
        }
        return $this->render(null, $params);
    }

    protected function getParamForAction($a, $mode = null)
    {
        $params = $this->getAppOwner()->getConfigs('controllers.' . $this->getControllerName() . '.' . $a, array());
        $params ['asseturl'] = BASE_URL . 'assets/';
        $params ['assetcdn'] = ASSET_URL;
        $params ['baseurl'] = BASE_URL;
        $params ['appurl'] = APP_URL;
        $params ['appasseturl'] = ASSET_URL . '/applications/' . $this->getAppOwner()->getAppId() . '/assets/';
        return $params;
    }

    protected function getActions($a)
    {
        return null;
    }

    protected function renderStaticContent($a, $f, $template = null, $params = null)
    {
        $params = $params ? $params : $this->getParamForAction($a);
        $action = $this->getActions($a);
        $retval = '';
        if ($action) {
            $retval = HTMLUtils::renderLinks($action, array(
                'class' => 'actions ' . $a . '-actions'
            ));
        }
        if (is_file($f) && is_readable($f)) {
            $params ['content'] = TemplateHelper::renderString(file_get_contents($f), $params, $this, \Utils::getFileExt($f, false));
        } else {
            $params ['content'] = 'content file not found ' . ($this->getAppOwner()->isDebugMode() ? $f : '');
        }
        $tpl = $template ? $this->getFile($this->getControllerName(), $template, 'Views') : null;
        $retval .= $tpl ? TemplateHelper::renderFile($tpl, $params, $this) : $params ['content'];
        return $retval;
    }

    protected function isOriginalRequest()
    {
        return $this->getControllerName() === $this->getAppOwner()->getRoute('_c');
    }

    function reset()
    {
    }

    function unhandledAction($action)
    {
        return null;
    }
}

?>

<?php
namespace System\MVC;
use System\Web\UI\Controls\Anchor;

use System\DB\Table;
use System\ACL\ACLHelper;
use System\Exceptions\AccessDeniedException;
use Strings;
use Request;
use Logger;
use System\Session\Session;
use System\Web\UI\Items\MenuItem;
use System\Web\Utils\HTMLUtils;
use System\Template\TemplateHelper;
use CGAF, Utils;
use System\Exceptions\SystemException;
use System\Exceptions\InvalidOperationException;
use System\JSON\JSONResult;
use URLHelper;

/**
 *
 */
interface IController {
	/**
	 * Enter description here .
	 * ..
	 *
	 * @param $route       mixed
	 * @param $vars        mixed
	 * @param $contentOnly boolean
	 */
	function render($route = null, $vars = null, $contentOnly = null);
}

/**
 *
 */
abstract class Controller extends \BaseObject implements IController, \ISearchProvider {
	/**
	 * @var Application
	 */
	private $_appOwner;
	//private $_viewPath;
	//private $_tpl;
	protected $_vars;
	/**
	 * @var string
	 */
	private $_routeName;
	protected $_clientContentId = "#maincontent";
	/**
	 * @var Model
	 */
	private $_models=array();
	protected $_initialized = false;
	protected $_warning = null;
	protected $_renderInternalAction = true;
	protected $_actionAttr = null;
	private $_currentAction;

	/**
	 * @param Application $appOwner
	 * @param null        $routeName
	 */
	function __construct(Application $appOwner, $routeName = null) {
		$this->_appOwner = $appOwner;
		$this->_routeName = $routeName;
		if (!$this->Initialize()) {
			throw new SystemException ('unable to initialize controller %s', $this->getControllerName());
		}
	}

	protected function isFromHome() {
		return $this->getAppOwner()->isFromHome();
	}

	/**
	 * @param      $controller
	 * @param bool $throw
	 * @return Controller
	 */
	protected function getController($controller, $throw = true) {
		if ($controller === $this->getControllerName()) {
			return $this;
		}
		return $this->getAppOwner()->getController($controller, $throw);
	}

	/**
	 * @param      $a
	 * @param null $param
	 */
	protected function redirect($a, $param = null) {
		\Response::Redirect(\URLHelper::add(APP_URL, $this->getControllerName() . '/' . $a, $param));
	}

	/**
	 * @param null $o
	 * @param null $id
	 * @param null $route
	 * @return string
	 */
	public function renderActions($o = null, $id = null, $route = null,$params=null) {
		$actions = $this->getAction($o, $id, $route,$params);
		return $this->render('actions', array(
				'actions' => $actions
		), true);
	}
	protected function _isAllowAction($id,$access) {
		return true;
	}
	/**
	 * @param      $o string|Table
	 * @param null $id
	 * @param null $route
	 * @return array
	 */
	protected function getAction($o, $id = null, $route = null,$params=null) {
		$retval = array();
		$id = $id !==null ? $id :\Request::get('id');
		if (!$this->_renderInternalAction)
			return $retval;

		$params = \Request::getIgnore(array(
				'CGAFSESS',
				'__url',
				'__c',
				'__a',
				'id'
		));
		$attrs =$this->_actionAttr;
		$route = $route ? $route : $this->getControllerName();
		$controller =$this;
		if ($route !== $this->getControllerName()) {
			$controller= $this->getController($route,false);
			if (!$controller) {
				return $retval;
			}
		}
		$c = $this->getController($route);
		if (is_array($route)) {
			$croute = $route;
		} else {
			$r = MVCHelper::getRoute();
			$croute = array(
					'_a' => $r ['_a'],
					'_c' => $route
			);
		}
		$url = \URLHelper::add(APP_URL, $route);
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
				if ($croute ['_a'] !== 'aed' && $this->_isAllowAction($id,'add')) {
					$retval [] = new Anchor(\URLHelper::Add($url, 'aed',$params),  __('action.add'), HTMLUtils::mergeAttr($this->_actionAttr,array('class'=>'icon-plus')));
				}
				if ($this->isAllow('manage')) {
					$retval [] = new Anchor(\URLHelper::Add($url, 'manage',$params), __('action.manage'), HTMLUtils::mergeAttr($this->_actionAttr,array('class'=>'icon-list-alt')));
				}
			}
		}
		if ($id) {

			if ($this->isAllow('detail') && $croute ['_a'] !== 'detail') {
				$retval [] = new Anchor(\URLHelper::Add($url, 'detail', 'id=' .(is_array($id) ? implode(',',$id):$id)), __('action.detail'), HTMLUtils::mergeAttr($this->_actionAttr,array('class'=>'icon-book')));
			}
			if ($this->isAllow('edit') && $this->_isAllowAction($id,'edit')) {
				$retval [] = new Anchor(\URLHelper::Add($url, 'aed', 'id=' . $id), __('action.edit'), HTMLUtils::mergeAttr($this->_actionAttr,array('class'=>'icon-edit')));
			}
			if ($this->isAllow('delete') && $this->_isAllowAction($id,'delete')) {
				$retval [] = new Anchor(\URLHelper::Add($url, 'del', 'id=' . $id), __('action.delete'), HTMLUtils::mergeAttr($this->_actionAttr,array('class'=>'icon-trash')));
			}
		}
		return $retval;
	}

	/**
	 * @param $stateName
	 * @param $value
	 */
	function setState($stateName, $value) {
		$stid = $this->getAppOwner()->getAppId() . '-' . $this->getControllerName();
		return Session::setState($stid, $stateName, $value);
	}

	/**
	 * @param      $stateName
	 * @param null $default
	 */
	function getState($stateName, $default = null) {
		$stid = $this->getAppOwner()->getAppId() . '-' . $this->getControllerName();
		return Session::getState($stid, $stateName, $default);
	}

	/**
	 * @return string
	 */
	function name() {
		return $this->_routeName;
	}

	protected function addClientAsset($asset, $group = null) {
		return $this->getAppOwner()->addClientAsset($asset, $group);
	}

	protected function _getControllerMenu(Model $m, $row) {
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

	function renderModules($position) {
		return $this->renderContent($position);
	}

	protected function getLiveData($data) {
		return $this->getAppOwner()->getLiveAsset($data);
	}

	protected function getInternalPath($o = null, $create = true) {
		return $this->getAppOwner()->getInternalStorage('data/'.$this->getControllerName().DS. $o, $create);
	}
	protected function getLivePath($o=null,$create=true) {
		$path = $this->getAppOwner()->getLivePath(false).$this->getControllerName().$o.DS;
		if (!is_dir($path) && $create) {
			return \Utils::makeDir($path);
		}
		return $path;
	}
	protected function setModel($model) {
		if (is_string($model)) {
			$model = $this->getAppOwner()->getModel($model);
		}
		$this->_models[$this->getControllerName()] = $model;
	}

	protected function getConfig($configName, $def = null) {
		return $this->getAppOwner()->getConfig($this->getControllerName() . '.' . $configName, $def);
	}

	protected function getUserConfig($configName, $def = null) {
		return $this->getAppOwner()->getUserConfig($this->getControllerName() . '.' . $configName, $def);
	}

	function _changeApp() {
	}

	protected function getManageAction() {
		if ($this->_currentAction == null) {
			$this->_currentAction = strtolower(Request::get("_gridAction", Request::get("action", Request::get("__a", Request::get("oper")))));
		}
		return $this->_currentAction;
	}

	protected function getWarningText($li = true) {
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

	function detail($args = null, $return = null) {
		$args = $args ? $args : array();
		$id = Request::get('id', isset ($args ['id']) ? $args ['id'] : null);
		$m = $this->getModel();
		if (!$m) {
			throw new SystemException ('unable to get model on controller' . $this->getControllerName());
		}
		if ($id !== null) {
			$row = $m->reset()->whereId($id)->loadObject();
			$args ['row'] = $row;
		} else {
			throw new InvalidOperationException ('Invalid ID');
		}
		$menus = $this->renderControllerMenu($m, $row);
		$args = array_merge(array(
				'menus' => $menus
		), $args);
		return $this->render(array(
				'_a' => 'detail'
		), $args, $return);
	}

	function undel($id = null) {
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

	function del($id = null) {
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

	function store() {
		$m = $this->getModel();
		if (!$m) {
			throw new SystemException ('Invalid Model');
		}
		$retval = new JSONResult (false, 'data.storefailed');
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

	function edit($row = null) {
		return $this->aed($row);
	}

	function add() {
		return $this->aed();
	}

	function aed($row = null, $action = 'aed', $args = array()) {
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
		return $this->render(array(
				'_a' => $action
		), $args);
	}

	protected function renderControllerMenu($m, $row) {
		$items = $this->_getControllerMenu($m, $row);
		return HTMLUtils::renderLinks($items, array(
				'class' => 'controller-menu'
		));
	}

	function manage($vars = null, $newroute = null, $return = false) {
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
			case 'add' :
			case "edit" :
				return $this->edit();
				break;
			case "store" :
				return $this->store();
				break;
			case "detail" :
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
	function getItemContents($position) {
		return $this->getAppOwner()->getItemContents($position,  $this->getControllerName());
	}
	function renderContent($position, $params = null,$tabMode=false) {
		return $this->getAppOwner()->renderContent($position, $this->getControllerName(), false, true, $params,$tabMode);
	}

	function isAllowItem($itemid, $access = "view") {
		// special
		if ($this->isAllow(ACLHelper::ACL_EXT_1)) {
			return true;
		}
		return $this->getAppOwner()->getACL()->isAllow($itemid, $this->getControllerName(), $access);
	}

	public function handleAccessDenied($action) {
		return false;
	}

	/**
	 * check for security
	 *
	 * @param $access string
	 * @return bool
	 */
	public function isAllow($access = 'view') {
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
				break;
		}
		$retval = $this->getAppOwner()->isAllow($this->getControllerName(), "controller", $access);
		if ($access !== 'view') {
			$retval = $retval && (Session::get('fromhome') || $this->getAppOwner()->isAuthentificated());
		}
		return $retval;
	}

	function applist() {
		$this->getTemplate()->clear('js');
		return $this->render(array(
				'_c' => 'shared',
				'_a' => __FUNCTION__
		), array(
				'rows' => \AppManager::getInstalledApp()
		));
	}

	public function checkAccess($access) {
		$allow = $this->isAllow($access);
		if (!$allow) {
			if (Request::isAJAXRequest()) {
				throw new AccessDeniedException ("Access $access denied. to Controller " . $this->getRouteName());
			} else {
				\Response::RedirectToLogin("Access $access denied. to Controller " . $this->getRouteName());
			}
		}
		return true;
	}

	function search($s, $config) {
		$s = $s ? $s : \Request::get('q');
		$row = \Request::gets('p',true,false,false);
		$rows= array();
		if ($s && $m=$this->getModel()) {
			$rows = $m->search($s,isset($config['field']) ? $config['field'] : null,$config);
		}
		return $this->renderView('search-result',array('rows'=>$rows,'row'=>$row));
	}

	function getDbConnection() {
		return $this->getAppOwner()->getDBConnection();
	}

	function getControllerName() {
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
	 * ..
	 *
	 * @deprecated please use getControllerName
	 * @return string
	 */
	function getRouteName() {
		return $this->getControllerName();
	}

	/**
	 *
	 * @return \System\ACL\IACL
	 */
	protected function getACL() {
		return $this->getAppOwner()->getACL();
	}

	protected function getManageMenu() {
		$items = $this->getMenuItems('manage', 0, '/manage', true);
		$acl = $this->getACL();
		$manage = array();
		foreach ($items as $v) {
			$act = ($v instanceof MenuItem ? ($v->real_menuaction ? $v->real_menuaction : $v->getMenuAction()) : $v->menu_action);
			if ($acl->isAllow($act, 'manage')) {
				$manage [] = $v;
			}
		}
		return $manage;
	}

	public function getMenuItems($position, $parent = 0, $actionPrefix = null, $showIcon = true) {
		$rows = $this->getAppOwner()->getMenuItems($position, $parent, $actionPrefix, $showIcon,true,false,$this->getRouteName());
		$filtered = array();
		if (count($rows)) {
			$route = $this->getAppOwner()->getRoute();
			$rname = $route ["_c"];
			$a = $route ['_a'];
			/**
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
				$row->childs = array();
				if ($row->getId() !== $parent) {
					$row->childs = $this->getMenuItems($position, $row->getId(), $actionPrefix, $showIcon);
				}
				$filtered [] = $row;
			}
		}
		return $filtered;
	}

	function menu($return = false, $params) {
		$params = $params ? $params : array();
		$params ['position'] = isset ($params ['position']) ? $params ['position'] : $this->getControllerName();
		$params ['style'] = isset ($params ['style']) ? $params ['position'] : $this->getControllerName() . '-menu';
		return $this->renderMenu($params ['position'], $params ['style']);
	}

	function renderMenu($position, $ulclass = null, $showIcon = false, $replacer = array()) {
		if (is_array($position)) {
			$filtered = $position;
			$position = "custom";
		} else {
			$filtered = $this->getMenuItems($position, 0, null, $showIcon);
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
		}
		$retval = "<div class=\"menu-container\" id='menu-container-$position'>";
		if (!count($filtered) && $position != "menu-bar") {
			if (CGAF_DEBUG) {
				$retval .= "<div class=\"warning\">Menu not found for position $position @" . $this->getControllerName();
				$retval .= " app : " . $this->getAppOwner()->getAppId() .' Controller '.$this->getRouteName() .'</div>';
			}
		}
		$retval .= HTMLUtils::renderMenu($filtered, null, $ulclass . " menu-$position", $replacer, 'menu-' . $position);
		$retval .= "</div>";
		return $retval;
	}

	protected function Initialize() {
		if ($this->_initialized) {
			return true;
		}
		// $this->getAppOwner()->getRequestAction()
		if (!$this->isAllow()) {
			throw new AccessDeniedException ("Access denied to Controller %s", $this->getControllerName());
			//return false;
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
	function getAppOwner() {
		return $this->_appOwner;
	}

	function Assign($var, $val = null) {
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

	protected function getVars($varname = null, $default = null) {
		if ($varname === null) {
			return $this->_vars;
		}
		return isset ($this->_vars [$varname]) ? $this->_vars [$varname] : $default;
	}

	protected function getVar($name) {
		return isset ($this->_vars [$name]) ? $this->_vars [$name] : null;
	}

	function Index() {
		return $this->render('index', $this->_vars);
	}

	function getClassInstance($className, $suffix, $args = null) {
		return $this->getAppOwner()->getClassInstance($className, $suffix, $args);
	}

	/**
	 *
	 * @param
	 *	        $modelName
	 * @return \System\MVC\Model|\System\MVC\Models\TreeModel
	 */
	function getModel($modelName = null) {
		if ($modelName == null) {
			$modelName = $this->getControllerName();
		}
		if (isset($this->_models[$modelName])) {
			return $this->_models[$modelName];
		}else{
			$this->_models[$modelName]= $this->getAppOwner()->getModel($modelName);
		}
		return $this->_models[$modelName];
	}

	function getFile($viewName, $a, $prefix, $forceThrow = true) {
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

	public function renderView($viewName, $params = array(), $contentOnly = true) {
		if (!isset ($params ['controller'])) {
			$params ['controller'] = $this;
		}
		return $this->render(array(
				'_c' => $this->getControllerName(),
				'_a' => $viewName
		), $params, $contentOnly);
	}

	public function getView($viewName, $a = null, $attr = null, $classOnly = false) {
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
		} else {
			Logger::info('View Class Not found %s', $viewName);
		}
		try {
			$f = $this->getFile(strtolower($viewName), $a, 'Views', false);
			if ($f == null) {
				$f = $this->getFile($viewName, 'index', 'Views', false);
			}
			return TemplateHelper::renderFile($f, $attr, $this);
		} catch (\Exception $e) {
			return $e->getMessage();
		}
	}

	function preRender($route, $contentOnly = false) {
		if (!$contentOnly) {
			// $r = $this->getAppOwner()->getRoute();//::get ( "__route" );
			// if ($r !== null && $route ["_c"] != $r ["_c"] && $route ["_a"] !=
			// $r ["_a"]) {
			// $content = $this->getView ( $r ["_c"], $r ["_a"] );
			// $this->Assign ( "content", $content );
			// }
		}
	}

	function initAction($action, &$params) {
		if (!Request::isDataRequest()) {
			$this->getAppOwner()->addClientAsset($this->getControllerName() . '.css');
			$this->getAppOwner()->addClientAsset($this->getControllerName() . '.js');
		}
		if (is_array($params)) {
			foreach ($params as $k => $v) {
				$this->_vars [$k] = $v;
			}
		}
		return true;
	}

	protected function renderContentOnly() {
		return null;
	}

	function render($route = null, $vars = null, $contentOnly = null) {
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
		$vars ["content"] = (is_object($content) && $content instanceof \IRenderable) ? $content->render(true) : $content;
		// $this->Assign("content", $vars["content"]);
		if ($contentOnly) {
			return $vars ["content"];
		} else {
			$retval = $vars ["content"];
		}
		return $retval;
	}

	public function getActionAlias($action) {
		return $action;
	}

	public function prepareRender() {
	}

	public function handleResult($params) {
		if (Request::isJSONRequest()) {
			return new JSONResult (1, '', null, $params);
		}
		return $this->render(null, $params);
	}

	protected function getParamForAction($a, $mode = null) {
		$params = $this->getAppOwner()->getConfigs('controllers.' . $this->getControllerName() . '.' . $a, array());
		$params ['asseturl'] = BASE_URL . 'asset/get/?appId=' . $this->getAppOwner()->getAppId() . '&q=';
		$params ['baseurl'] = BASE_URL;
		$params ['appurl'] = APP_URL;
		$params ['appasseturl'] = ASSET_URL . '/applications/' . $this->getAppOwner()->getAppId() . '/assets/';
		return $params;
	}

	protected function getActions($a) {
	}

	protected function renderStaticConent($a, $f, $template = null) {
		$params = $this->getParamForAction($a);
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
}

?>

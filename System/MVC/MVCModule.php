<?php
namespace System\MVC;
use System\Session\Session;

use System\MVC\Controller;
use System\IApplicationModule;
use CGAF;
use ModuleManager;
abstract class MVCModule extends Controller implements IApplicationModule {
	abstract function handleService($serviceName);
	protected $_keyID;
	protected $_stateGroup;
	protected $_modInfo;
	protected $_mod_name = null;
	protected $_modulePath;
	function __construct($appOwner, $moduleName, $modulePath) {
		if (! $moduleName) {
			$moduleName = strtolower ( get_class ( $this ) );
		}
		$this->_modulePath = $modulePath;
		parent::__construct ( $appOwner, $moduleName );
	}
	function getModuleName() {
		return parent::getControllerName ();
	}
	function getModulePath() {
		return $this->_modulePath;
	}
	protected function Initialize() {
		parent::Initialize ();
		$this->_modInfo = ModuleManager::getModuleInfo ( $this->getModuleName () );
		if (! $this->_modInfo) {
			return false;
		}
		
		$this->_stateGroup = $this->_stateGroup ? $this->_stateGroup : $this->getModuleName ();
		return true;
	}
	
	protected function setKeyID($value) {
		self::$_keyID = $value;
	}
	function getView($viewName, $a = null, $attr = null) {
		return parent::getView ( $viewName, $a, $attr );
	}
	public function getState($name) {
		return Session::getState ( $this->_stateGroup, $name );
	}
	
	function render($route = null, $vars = null, $contentOnly = null) {
		
		return parent::render ( $route, $vars, $contentOnly );
	}
}
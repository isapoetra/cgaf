<?php
namespace System\Web\UI\JQ;
use System\JSON\JSON;
use System\Configurations\Configuration;
use System\Web\UI\Controls\WebControl;
use \Request;
abstract class Control extends WebControl implements \IRenderable {
	private $_vars = array();
	protected $_jsMode = true;
	protected $_configs;
	protected $_events = array();
	protected $_jsClientObj = null;
	function __construct($id, $jsClientObj = null) {
		parent::__construct("div", false);
		if ($id) {
			$this->setId($id);
		}
		$this->_jsClientObj = $jsClientObj;
		$this->_configs = new Configuration(null, false);
	}
	function addEvent($eventName, $value, $replace = false) {
		$value = str_replace("\r", '', $value);
		$value = str_replace("\t", '', $value);
		$value = str_replace("\n", '', $value);
		$this->_events[$eventName][] = $value;
		//return $this->setConfig($eventName, $value, $replace);
	}
	protected function isValidConfig($configName) {
		return !empty($configName);
	}
	protected function setConfigs($configs) {
		if (is_array($configs)) {
			$configs = new Configuration($configs, false);
		}
		$this->_configs = $configs;
	}
	function setOption($name, $value = null, $overwrite = true) {
		return $this->setConfig($name, $value, $overwrite);
	}
	function setConfig($configName, $value = null, $overwrite = true) {
		if (!is_object($this->_configs)) {
			ppd($this);
		}
		if ($configName) {
			$this->_configs->setConfig($configName, $value, $overwrite);
		}
		return $this;
	}
	function getClientId() {
		return $this->getId();
	}
	function removeConfig($configName) {
		/*if (is_array($configName)) {
		    foreach($configName as $v) {
		    $this->removeConfig($v);
		    }
		    return $this;
		    }
		    if (isset($this->_configs[$configName])) {
		    unset($this->_configs[$configName]);
		    }*/
		$this->_configs->remove($configName);
		return $this;
	}
	function getConfig($key, $def = null) {
		return $this->_configs->getConfig($key, $def);
	}
	function __get($name) {
		return isset($this->_vars[$name]) ? $this->_vars[$name] : parent::__get($name);
	}
	function __set($name, $value) {
		$this->_vars[$name] = $value;
	}
	function prepareRender() {
		return parent::prepareRender();
	}
	function getScript() {
		if ($this->_jsClientObj) {
			$id = $this->getClientId();
			if (!isset($this->_varName)) {
				$this->_varName = 'api' . $this->_jsClientObj;
			}
			$js = "var {$this->_varName}=$(\"#$id\").{$this->_jsClientObj}(" . JSON::encodeConfig($this->_configs) . ");";
			return $js . $this->_js;
		}
	}
	function RenderScript($return = false) {
		if ($this->_jsClientObj) {
			$this->AppOwner->addClientScript($this->getScript());
			$retval = parent::Render(true);
			if (!$return) {
				Response::write($retval);
			}
			return $retval;
		}
		return parent::Render($return);
	}
	protected function renderJSON($return = true) {
	}
	function loadJS($js) {
		return $this->AppOwner->addClientAsset($js);
	}
	function Render($return = false) {
		$this->prepareRender();
		if (Request::isDataRequest()) {
			return $this->renderJSON();
		} elseif (Request::isSupport("javascript", true)) {
			return $this->RenderScript($return);
		} else {
			return parent::Render($return);
		}
	}
}
?>
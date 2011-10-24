<?php
namespace System\Collections\Items;
use System\Assets\AssetHelper;
use \AppManager;
use \Utils;
class AssetItem extends \Object implements \IRenderable, \IItem {
	private $_resource;
	private $_liveResource = false;
	private $_group = null;
	private $_type = 'js';
	function __construct($resource, $group = null) {
		parent::__construct();
		$this->Resource = $resource;
		$this->_group = $group;
	}
	function getResource() {
		return $this->_resource;
	}
	private function isType($type) {
		$ext = Utils::getFileExt($this->_resource, false);
		if ((strpos($ext, '/') !== false)) {
			return strpos($ext, $type) !== false;
		}
		return strtolower($ext) === $type;
	}
	private function _getLiveResourceBytype($res, $type) {
		if (is_array($res)) {
			$retval = array();
			foreach ($res as $r) {
				$retval[] = $this->_getLiveResourceBytype($r, $type);
			}
			return $retval;
		} elseif (is_string($res)) {
			if (strpos($res, '.' . $type) !== false || $this->isType($type)) {
				return new AssetItem($res, $this->_group);
			}
		}
	}
	function getLiveResourceByType($type) {
		$res = $this->LiveResource;
		return $this->_getLiveResourceByType($res, $type);
	}
	function equals($item) {
		if ($item instanceof AssetItem) {
			return $this->LiveResource === $item->LiveResource;
		}
		return $this->_resource === $item;
	}
	function setResource($value) {
		$this->_resource = $value;
		$this->_liveResource = AppManager::getInstance()->getLiveAsset($value);
	}
	function getLiveResource() {
		if ($this->_liveResource === false) {
			$this->_liveResource = AppManager::getInstance()->getLiveAsset($this->_resource);
			if (!$this->_liveResource) {
				Logger::Warning("Unable to Find resource for live " . $this->_resource);
			}
		}
		return $this->_liveResource;
	}
	function Render($return = false) {
		if (!$this->LiveResource) {
			Logger::Warning('Unable to find asset live for ' . $this->_resource);
			return null;
		}
		$attr = array();
		if ($this->_group) {
			if (is_string($this->_group)) {
				$attr['id'] = $this->_group;
			} else {
				$attr = $this->_group;
			}
		}
		$retval = AssetHelper::renderAsset($this->LiveResource, $attr);
		if (!$return) {
			Response::write($retval);
		}
		return $retval;
	}
}

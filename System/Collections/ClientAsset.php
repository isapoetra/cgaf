<?php
class AssetItem extends Object implements IRenderable,IItem {
	private $_resource;
	private $_liveResource;
	private $_resourceType;
	function __construct($resource) {
		parent::__construct();
		$this->Resource = $resource;
	}
	function getResource() {
		return $this->_resource;
	}
	function getType() {
		return strtolower(Utils::getFileExt($this->_resource,false));
	}
	function isType($type) {
		return $this->getType()===$type;
	}
	function equals($item) {
		if ($item instanceof IItem) {
			return $this->_resource === $item->Resource;
		}
		return $this->_resource === $item;
	}
	function setResource($value) {
		$this->_resource = $value;
		$this->_resourceType = Utils::getFileExt($this->_resource,false);
		$this->_liveResource = AppManager::getInstance()->getLiveAsset($value);
	}
	function getLiveResource() {
		if ($this->_liveResource===null) {
			$this->_liveResource =  AppManager::getInstance()->getLiveAsset($this->_resource);
			if (!$this->_liveResource) {
				Logger::Warning("Unable to Find resource for live ".$this->_resource);
			}
		}
		return $this->_liveResource;
	}

	function Render($return=false) {
		if (!$this->LiveResource) {
				
			return null;
		}

		switch (strtolower($this->_resourceType)) {
			case "js":
				$retval =  '<script language="javascript" type="text/javascript" src="' . $this->_liveResource . '"></script>';
				break;
			case "css":
				$retval = '<link rel="stylesheet" type="text/css" media="all" href="' . $this->_liveResource . '"/>';
				break;
			default:
				throw new SystemException("Unhandle asset type ".$ext);
		}
		if (!$return) {
			Response::write($retval);
		}
		return $retval;
	}
}
class ClientAssetCollections extends Collection implements IRenderable {
	private $_appOwner;
	function __construct(IApplication $owner) {
		parent::__construct(null,false,"AssetItem");
		$this->_appOwner = $owner;
	}
	function contains($item) {
		if (is_string($item)) {
			foreach ($this as $v) {
				if ($v->Resource ==$item) {
					return true;
				}
			}
			return false;
		}
		return parent::contains($item);
	}
	function Render($return=false,$type=null,$clear=false) {
		$retval = '';
		foreach ($this as $v) {
			if ($type) {
				if ($v->isType($type)) {
					$retval .= $v->Render(true);
				}

			}else{
				$retval .= $v->Render(true);
			}
		}
		if ($clear) {
			$this->clear();
		}
		return $retval;
	}
}
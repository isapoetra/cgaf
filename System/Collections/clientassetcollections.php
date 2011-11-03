<?php
namespace System\Collections;
use \System\Assets\AssetHelper;
use \System\Collections\Items\AssetItem;
using('System.Collections.Items.assetitem');
class ClientAssetCollections extends Collection implements \IRenderable {
	private $_appOwner;
	function __construct(\IApplication $owner) {
		parent::__construct(null, false, "System\\Collections\\Items\\AssetItem");
		$this->_appOwner = $owner;
	}
	function contains($item) {
		if (is_string($item)) {
			foreach ($this as $v) {
				if ($v->Resource == $item) {
					return true;
				}
			}
			return false;
		}
		return parent::contains($item);
	}
	function add($item, $group = null) {
		if (is_array($item)) {
			foreach ($item as $value) {
				$this->add($value, $group);
			}
			return $this->_c - 1;
		} elseif (is_string($item)) {
			return $this->add(new AssetItem($item, $group));
		}
		return parent::add($item);
	}
	function Render($return = false, $type = null, $clear = false) {
		$retval = '';
		foreach ($this->toArray() as $v) {
			if ($type) {
				$item = $v->getLiveResourceByType($type);
				if ($item) {
					$retval .= AssetHelper::renderAsset($item);
				}
			} else {
				$retval .= $v->Render(true);
			}
		}
		if ($clear) {
			$this->clear();
		}
		return $retval;
	}
}
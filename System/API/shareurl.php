<?php
namespace System\API;
class shareurl extends PublicApi {
	private $_defaultImage;
	private static function initAsset() {
		static $init;
		if ($init)
			return;
		$init = true;
		\AppManager::getInstance()->addClientAsset('share.css');
	}
	function init($service) {
		self::initAsset();
		$this->_defaultImage = $this->getAppOwner()->getLiveAsset('share/sprite.png');
	}
	public function parse($v) {
		$configs = $this->_config->getConfigs('System');
		$v = \Strings::Replace($v, $configs, $v, true, null, '{', '}', true);
		return $v;
	}
	function url($config, $id = null, $multi = true, $imageIndex = 0) {
		$this->init(__FUNCTION__);
		if ($multi) {
			$retval = '<ul class="share-url">';
			$idx = 0;
			foreach ($config as $k => $v) {
				$retval .= $this->url($v, $k, false, $idx);
				$idx++;
			}
			$retval .= '</ul>';
		} else {
			$t = array();
			foreach ($config as $kk => $vv) {
				$val = $this->parse($vv);
				if ($kk === 'shareurl') {
					$val = $val;
				}
				$t[$kk] = $val;
			}
			$url = BASE_URL . '/share/?service=shareurl&id=' . $id . '&url=' . urlencode($this->getConfig('url')) . '&description=' . urlencode($this->getConfig('description')) . '&title=' . urlencode($this->getConfig('title')) . '&tags='
					. urlencode($this->getConfig('tags'));// $t['shareurl'];
			$descr = isset($t['descr']) ? $t['descr'] : ucwords($id);
			$title = isset($t['title']) ? $t['title'] : ucwords($id);
			$retval = '<li  class="share-item">';
			$retval .= '<a href="' . $url . '" alt="' . $descr . '" title="' . $descr . '" class="' . strtolower($id) . '" target="__blank">';
			$retval .= ' <span class="bg">&nbsp;</span>';
			if ($this->getConfig('showtitle')) {
				$retval .= '<span>' . $title . '</span>';
			}
			//$retval .= $title;
			$retval .= '</a>';
			$retval .= '</li>';
		}
		return $retval;
	}
}

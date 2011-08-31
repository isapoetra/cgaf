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
	private function parse($v) {
		$configs = $this->_config->getConfigs('System');
		$v = \String::Replace($v, $configs, $v, true, null, '{', '}');
		return $v;
	}
	function url($config, $title = null, $multi = true, $imageIndex = 0) {
		$this->init(__FUNCTION__);
		if ($multi) {
			$retval = '<ul>';
			$idx = 0;
			foreach ($config as $k => $v) {
				$retval .= $this->url($v, $k, false, $idx);
				$idx++;
			}
			$retval .= '</ul>';
		} else {
			$t = array();
			foreach ($config as $kk => $vv) {
				$t[$kk] = $this->parse($vv);
			}
			$imageX = isset($t['imageX']) ? $t['imageX'] : '-94px';
			$url = $t['shareurl'];
			$descr = isset($t['descr']) ? $t['descr'] : $title;
			$image = $this->getAppOwner()->getLiveAsset(isset($t['image']) ? $t['image'] : 'share/' . strtolower($title) . '.png');
			$style = null;
			if ($image) {
				$style .= 'background-image:url(\'' . $image . '\');background-repeat:no-repeat;';
			}
			if (isset($t['imageCoord'])) {
				$style .= 'background-position:' . $t['imageCoord'];
			} else {
				$style .= 'background-position:' . $imageX . ' ' . ($imageIndex * 16 * -1) . 'px';
			}
			$retval = '<li>';
			$retval .= '<a href="' . $url . '" alt="' . $descr . '" title="' . $descr . '" ' . ($style ? $style : '') . ' class="share-item">';
			$retval .= ' <span class="bg" style="' . $style . '">&nbsp;</span>';
			$retval .= '<span>' . $title . '</span>';
			$retval .= '</a>';
			$retval .= '</li>';
		}
		return $retval;
	}
}

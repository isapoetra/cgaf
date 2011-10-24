<?php
namespace System\API;
class Skype extends PublicApi {
	function init($service) {
		switch (strtolower($service)) {
		case 'onlinestatus':
			$this->getAppOwner()->addClientAsset('http://download.skype.com/share/skypebuttons/js/skypeCheck.js');
			break;
		default:
			;
			break;
		}
		return parent::init($service);
	}
	function onlineStatus($config = null) {
		if (is_string($config)) {
			$config = array(
					'username' => $config);
		}
		$def = array(
				//'image' => 'call_green_white_153x63.png'
				'image' => 'call_blue_white_124x52.png');
		$config = \Utils::arrayMerge($def, $config);
		if (!isset($config['username'])) {
			return null;
		}
		return '<a href="skype:' . $config['username'] . '?call"><img src="http://download.skype.com/share/skypebuttons/buttons/' . $config['image'] . '" style="border: none;" width="153" height="63" alt="Skype Me™!" /></a>';
	}
}

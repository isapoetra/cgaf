<?php
namespace System\Auth;
use System\Web\Utils\HTMLUtils;
use \URLHelper;
use \AppManager;

class Auth {

	public static function getProviders() {
		$providers = array(
				'google-oauth' => array('popup' => true,
						'title' => __('auth.google.title'),
						'login-url' => BASE_URL . 'auth/google'),
				'facebook' => array('title' => __('auth.facebook.title'),
						'login-url' => BASE_URL . 'auth/facebook'),
				'oauth' => array('title' => __('auth.oauth.title'),
						'login-url' => BASE_URL . 'auth/openid'));
		return $providers;
	}

	public static function renderProviders() {
		$providers = self::getProviders();
		foreach ($providers as $id => $p) {
			if (isset($p['jsfile'])) {
				AppManager::getInstance()->addClientAsset($p['jsfile']);
			}
			if (isset($p['jscript'])) {
				AppManager::getInstance()->addClientScript($p['jscript']);
			}
			$img = isset($p['image']) ? $p['image'] : 'auth/' . $id . '.png';
			$url = isset($p['login-url']) ? $p['login-url']
					: BASE_URL . 'auth/external/?id=' . $id;
			echo HTMLUtils::renderLink($url, $p['title'],
					array('id' => 'auth-' . $id,'role'=>'button'), $img);
		}
		/**/
	}
}

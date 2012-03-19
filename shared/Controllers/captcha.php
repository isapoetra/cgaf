<?php
namespace System\Controllers;
use \System\Captcha\Captcha;
use System\Exceptions\SystemException;

use System\MVC\Controller;
use System\Configurations\Configuration;
use System\Session\Session;
use System\MVC\Application;
use \Request;

class CaptchaController extends Controller {

	private $_options;

	/**
	 * @param \System\MVC\Application $appOwner
	 */
	function __construct(Application $appOwner) {
		parent::__construct($appOwner, "captcha");
	}

	function isValidCaptcha($captchaId = null, $throw = true) {
		return $this->getCaptcha()->validateRequest($captchaId);
	}

	/**
	 * @return \System\Captcha\ICaptcha
	 */
	private function getCaptcha() {
		static $c;
		if (!$c) {
			$c = Captcha::getInstance($this->getAppOwner());
		}
		return $c;
	}

	function isAllow($access = "view") {
		switch ($access) {
			case 'view' :
			case 'index':
			case 'html' :
				return true;
				break;
		}
		return parent::isAllow($access);
	}


	/**
	 *
	 */
	function Index() {
		\Request::isDataRequest(true);
		$capchaId = Request::Get("__captchaId", "__captcha");
		$mode = Request::get("__captchaMode", "image");
		$captcha = $this->getCaptcha();
		header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
		header("Last-Modified: " . gmdate("D, d M Y H:i:s") . "GMT");
		header("Cache-Control: no-store, no-cache, must-revalidate");
		header("Cache-Control: post-check=0, pre-check=0", false);
		header("Pragma: no-cache");

		return $captcha->render(true);

	}


	/**
	 * @return \System\Configurations\Configuration
	 */
	public function getConfigs() {
		return $this->_options;
	}

}

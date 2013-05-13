<?php
/**
 * Captcha.php
 * User: e1
 * Date: 3/16/12
 * Time: 12:54 AM
 */
namespace System\Captcha;
use System\Applications\IApplication;

abstract class Captcha {
	/**
	 * @static
	 * @param \System\Applications\IApplication $appOwner
	 * @return ICaptcha
	 */
	public static function getInstance(IApplication $appOwner) {
		static $instance;
		if (!$instance) {
			$class = '\\System\\Captcha\\' . $appOwner->getConfig('captcha.engine','MyCaptcha');
			$instance = new $class($appOwner);
		}
		return $instance;
	}
}

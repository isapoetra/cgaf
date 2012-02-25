<?php
use System\Exceptions\SystemException;
final class Response {
	private static $_initialized = false;
	private static $_instance = null;
	private static $_flushed = false;
	public static function getInstance() {
		if (self::$_instance == null) {
			//using('System.'.CGAF_CONTEXT.".Response");
			$class = "System\\" . CGAF_CONTEXT . "\\Response";
			$instance = new $class();
			if (!($instance instanceof \IResponse)) {
				throw new SystemException('class ' . $class . 'not implement IResponse interface');
			}
			self::$_instance = $instance;
		}
		return self::$_instance;
	}
	public static function destroy() {
		self::$_instance = null;
	}
	public static function writeln($s, $attr = null) {
		$nl = "\n";
		self::getInstance()->write(Utils::toString($s) . $nl, $attr);
	}
	public static function write($s, $attr = null) {
		self::getInstance()->write($s, $attr);
	}
	public static function getBuffer() {
		return self::getInstance()->getBuffer();
	}
	public static function clearBuffer() {
		return self::getInstance()->clearBuffer();
	}
	public static function Flush($force = false) {
		if (self::$_instance && (!self::$_flushed || $force)) {
			self::getInstance()->flush();
		}
		self::$_flushed = true;
	}
	public static function StartBuffer() {
		return self::getInstance()->StartBuffer();
	}
	public static function EndBuffer($flush = false) {
		return self::getInstance()->EndBuffer($flush);
	}
	public static function forceContentExpires() {
		return self::getInstance()->forceContentExpires();
	}
	public static function Redirect($url = null) {
		return self::getInstance()->Redirect($url);
	}
	public static function JSON($code, $message, $redirect = null) {
		return array(
				'code' => $code,
				'message' => __($message),
				'_redirect' => $redirect);
	}
	public static function redirectToLogin($msg) {
		self::Redirect(BASE_URL . '?__c=auth&' . (Request::isAJAXRequest() ? '__ajax=1' : '') . '&redirect=' . htmlspecialchars(Request::getOrigin()) . '&msg=' . $msg);
	}
	public static function writeAt($x, $y, $s) {
		return self::getInstance()->writeAt($x, $y, $s);
	}
	public static function writeColor($s, $foreGround = null, $backGround = null, $style = null) {
		return self::getInstance()->writeColor($s, $foreGround, $backGround, $style);
	}
}
?>

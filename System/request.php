<?php
if (!defined("CGAF"))
	die("Restricted Access");
use System\Session\Session;
use System\Web\ClientInfo;
abstract class Request {
	private static $_instance;
	private static $_ajax;
	private static $_isJSONRequest;
	private static $_isXMLRequest;
	private static $_isDataRequest;
	private static $_clientInfo;
	private static $_isMobile;
	/**
	 * Enter description here...
	 *
	 * @return IRequest
	 */
	protected static function getInstance() {
		if (!self::$_instance) {
			$class = '\\System\\' . CGAF_CONTEXT . "\\Request";
			self::$_instance = new $class();
			if (self::get('__mobile')) {
				self::isMobile(true);
				if (CGAF_DEBUG) {
					\Request::getClientInfo()->setPlatform('android');
				}
			}
		}
		return self::$_instance;
	}
	public static function gets($place = null, $secure = true, $ignoreEmpty = false) {
		return self::getInstance()->gets($place, $secure, $ignoreEmpty);
	}
	public static function getIgnore($ignored, $secure = true, $place = null) {
		$req = self::gets($place, $secure, true);
		$retval = array();
		if (is_string($ignored)) {
			$ignored = array(
					$ignored);
		}
		foreach ($req as $k => $v) {
			if (!in_array($k, $ignored)) {
				$retval[$k] = $v;
			}
		}
		return $retval;
	}
	public static function isMobile($value = null) {
		if ($value !== null) {
			self::$_isMobile = $value;
		} else {
			if (self::$_isMobile === null) {
				self::$_isMobile = \Utils::toBool(self::getClientInfo()->isMobile());
			}
		}
		return self::$_isMobile;
	}
	public static function getClientPlatform() {
		$c = self::getClientInfo();
		return $c->getPlatform();
	}
	public static function isAJAXRequest($value = null) {
		if ($value !== null) {
			self::$_ajax = $value;
		}
		if (self::$_ajax == null) {
			self::$_ajax = (isset($_SERVER["HTTP_X_REQUESTED_WITH"]) && $_SERVER["HTTP_X_REQUESTED_WITH"] === "XMLHttpRequest") || Request::get("_ajax") || Request::get("__ajax") || Request::isJSONRequest();
		}
		return self::$_ajax;
	}
	public static function get($varName, $default = null, $secure = true, $place = null) {
		return self::getInstance()->get($varName, $default, $secure, $place);
	}
	public static function set($varName, $value) {
		if ($varName === '__data') {
			self::isDataRequest(true);
		}
		return self::getInstance()->set($varName, $value);
	}
	public static function getOrigin() {
		return self::getInstance()->getOrigin();
	}
	public static function isJSONRequest($value = null) {
		if ($value !== null) {
			self::$_isJSONRequest = $value;
			self::$_isDataRequest = true;
		}
		if (self::$_isJSONRequest === null) {
			$accept = isset($_SERVER["HTTP_ACCEPT"]) ? $_SERVER["HTTP_ACCEPT"] : null;
			self::$_isJSONRequest = strpos($accept, 'application/json') !== false || Request::get("__json") || Request::get("__data") === 'json' || Request::get("__s") === 'json';
		}
		//ppd($_SERVER);
		return self::$_isJSONRequest;
	}
	public static function isXMLRequest() {
		if (self::$_isXMLRequest === null) {
			self::$_isXMLRequest = (isset($_SERVER["HTTP_ACCEPT"]) && $_SERVER["HTTP_ACCEPT"] == "application/xml, text/xml, */*") || Request::get("__xml") || Request::get('__data', null, false) === 'xml';
		}
		return self::$_isXMLRequest;
	}
	public static function setDataRequest($value) {
		self::$_isDataRequest = $value;
	}
	public static function isDataRequest($value = null) {
		if ($value !== null) {
			self::isAJAXRequest(true);
			self::$_isDataRequest = true;
		} elseif (self::$_isDataRequest === null) {
			self::$_isDataRequest = self::isJSONRequest() || self::isXMLRequest() || self::get('__data') || self::get('__s');
		}
		return self::$_isDataRequest;
	}
	/**
	 *
	 * Enter description here ...
	 * @return System\Web\ClientInfo
	 */
	public static function &getClientInfo() {
		$ci =null;
		$ci = Session::get('__clientInfo');
		if (!$ci) {
			$ci = new ClientInfo(Utils::makeDir(CGAF::getInternalStorage('browsecap', false), 0700, '*'));

			Session::set('__clientInfo', $ci);
		}
		return $ci;
	}
	public static function isIE() {
		return self::getClientInfo()->isIE();
	}
	public static function isSupport($key, $default = false) {
		$browser = self::getClientInfo();
		return $browser->get($key, $default);
	}
	public static function getClientId() {
		$ip = $_SERVER['REMOTE_ADDR'];
		return $ip;
	}
	public static function isOverlay() {
		return self::get('__overlay') !== null;
	}
	public static function getQueryParams($ignore, $returnstring = false) {
		$params = self::getInstance()->gets(null, true);
		$retval = array();
		foreach ($params as $k => $v) {
			if (!in_array($k, $ignore)) {
				$retval[$k] = $v;
			}
		}
		return $returnstring ? Utils::arrayImplode($retval, '=', '&') : $retval;
	}
	public static function log(IApplication $app) {
		$ip = $_SERVER['REMOTE_ADDR'];
		$db = Utils::ToDirectory($app->getInternalStoragePath() . "/log/counter/ip.db");
		$path = dirname($db);
		Utils::makeDir($path);
		$file_ip = fopen($db, 'w+');
		$found = false;
		while (!feof($file_ip)) {
			$line[] = fgets($file_ip, 1024);
		}
		for ($i = 0; $i < (count($line)); $i++) {
			list($ip_x) = explode("\n", $line[$i]);
			if ($ip == $ip_x) {
				$found = 1;
			}
		}
		fclose($file_ip);
		if (!($found == 1)) {
			$file_ip2 = fopen($db, 'ab');
			$line = "$ip\n";
			fwrite($file_ip2, $line, strlen($line));
			$file_count = @fopen($path . DS . "count.db", 'r+');
			if (!$file_count) {
				return;
			}
			$data = '';
			while (!feof($file_count))
				$data .= fread($file_count, 4096);
			fclose($file_count);
			@list($today, $yesterday, $total, $date, $days) = explode("%", $data);
			if ($date == date("Y m d"))
				$today++;
			else {
				$yesterday = $today;
				$today = 1;
				$days++;
				$date = date("Y m d");
			}
			$total++;
			$line = "$today%$yesterday%$total%$date%$days";
			$file_count2 = fopen($path . DS . 'count.db', 'wb');
			fwrite($file_count2, $line, strlen($line));
			fclose($file_count2);
			fclose($file_ip2);
		}
	}
}
?>

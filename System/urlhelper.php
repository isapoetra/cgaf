<?php
define('ALLOWED_CHARS', '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ');
class URLHelper {
	private static $selfUrl;
	public static function getCurrentProtocol() {
		$protocol = isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] == 'on') ? 'https' : 'http';
		return $protocol;
	}
	public static function getOrigin() {
		static $origin;
		if (!$origin) {
			$origin = self::addParam(BASE_URL, Request::gets());
		}
		return $origin;
	}
	public static function selfUrl() {
		if (self::$selfUrl !== null) {
			return self::$selfUrl;
		}
		if (isset($_SERVER['SCRIPT_URI'])) {
			return $_SERVER['SCRIPT_URI'];
		}
		$url = '';
		$port = '';
		if (isset($_SERVER['HTTP_HOST'])) {
			if (($pos = strpos($_SERVER['HTTP_HOST'], ':')) === false) {
				if (isset($_SERVER['SERVER_PORT'])) {
					$port = ':' . $_SERVER['SERVER_PORT'];
				}
				$url = $_SERVER['HTTP_HOST'];
			} else {
				$url = substr($_SERVER['HTTP_HOST'], 0, $pos);
				$port = substr($_SERVER['HTTP_HOST'], $pos);
			}
		} else if (isset($_SERVER['SERVER_NAME'])) {
			$url = $_SERVER['SERVER_NAME'];
			if (isset($_SERVER['SERVER_PORT'])) {
				$port = ':' . $_SERVER['SERVER_PORT'];
			}
		}
		if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') {
			$url = 'https://' . $url;
			if ($port == ':443') {
				$port = '';
			}
		} else {
			$url = 'http://' . $url;
			if ($port == ':80') {
				$port = '';
			}
		}
		$url .= $port;
		if (isset($_SERVER['HTTP_X_REWRITE_URL'])) {
			$url .= $_SERVER['HTTP_X_REWRITE_URL'];
		} elseif (isset($_SERVER['REQUEST_URI'])) {
			$query = strpos($_SERVER['REQUEST_URI'], '?');
			if ($query === false) {
				$url .= $_SERVER['REQUEST_URI'];
			} else {
				$url .= substr($_SERVER['REQUEST_URI'], 0, $query);
			}
		} else if (isset($_SERVER['SCRIPT_URL'])) {
			$url .= $_SERVER['SCRIPT_URL'];
		} else if (isset($_SERVER['REDIRECT_URL'])) {
			$url .= $_SERVER['REDIRECT_URL'];
		} else if (isset($_SERVER['PHP_SELF'])) {
			$url .= $_SERVER['PHP_SELF'];
		} else if (isset($_SERVER['SCRIPT_NAME'])) {
			$url .= $_SERVER['SCRIPT_NAME'];
			if (isset($_SERVER['PATH_INFO'])) {
				$url .= $_SERVER['PATH_INFO'];
			}
		}
		self::$selfUrl = $url;
		return self::$selfUrl;
	}
	public static function parseURL($url) {
		$r = "(?:([a-z0-9+-._]+)://)?";
		$r .= "(?:";
		$r .= "(?:((?:[a-z0-9-._~!$&'()*+,;=:]|%[0-9a-f]{2})*)@)?";
		$r .= "(?:\[((?:[a-z0-9:])*)\])?";
		$r .= "((?:[a-z0-9-._~!$&'()*+,;=]|%[0-9a-f]{2})*)";
		$r .= "(?::(\d*))?";
		$r .= "(/(?:[a-z0-9-._~!$&'()*+,;=:@/]|%[0-9a-f]{2})*)?";
		$r .= "|";
		$r .= "(/?";
		$r .= "(?:[a-z0-9-._~!$&'()*+,;=:@]|%[0-9a-f]{2})+";
		$r .= "(?:[a-z0-9-._~!$&'()*+,;=:@\/]|%[0-9a-f]{2})*";
		$r .= ")?";
		$r .= ")";
		$r .= "(?:\?((?:[a-z0-9-._~!$&'()*+,;=:\/?@]|%[0-9a-f]{2})*))?";
		$r .= "(?:#((?:[a-z0-9-._~!$&'()*+,;=:\/?@]|%[0-9a-f]{2})*))?";
		preg_match("`$r`i", $url, $match);
		$parts = array(
				"scheme" => '',
				"userinfo" => '',
				"authority" => '',
				"host" => '',
				"port" => '',
				"path" => '',
				"query" => '',
				"fragment" => '');
		switch (count($match)) {
		case 10:
			$parts['fragment'] = $match[9];
		case 9:
			$parts['query'] = $match[8];
		case 8:
			$parts['path'] = $match[7];
		case 7:
			$parts['path'] = $match[6] . $parts['path'];
		case 6:
			$parts['port'] = $match[5];
		case 5:
			$parts['host'] = $match[3] ? "[" . $match[3] . "]" : $match[4];
		case 4:
			$parts['userinfo'] = $match[2];
		case 3:
			$parts['scheme'] = $match[1];
		}
		$parts['authority'] = ($parts['userinfo'] ? $parts['userinfo'] . "@" : "") . $parts['host'] . ($parts['port'] ? ":" . $parts['port'] : "");
		return $parts;
	}
	public static function explode($sUrl) {
		$aUrl = self::parseURL($sUrl);
		//pp ( $aUrl );
		if (!isset($aUrl['path'])) {
			$aUrl['path'] = '';
		}
		$aUrl['query_params'] = array();
		$aPairs = isset($aUrl['query']) ? explode('&', $aUrl['query']) : array();
		foreach ($aPairs as $sPair) {
			if (trim($sPair) == '') {
				continue;
			}
			$sKey = $sPair;
			$sValue = null;
			if (strpos($sPair, '=') > 0) {
				list($sKey, $sValue) = explode('=', $sPair);
			}
			$aUrl['query_params'][$sKey] = urldecode($sValue);
		}
		$aUrl['path'] = $aUrl['path'] ? Utils::explode("/", $aUrl['path'], true) : array();
		return $aUrl;
	}
	public static function implode($aUrl) {
		$sQuery = '';
		// Compile query
		if (isset($aUrl['query_params']) && is_array($aUrl['query_params'])) {
			$aPairs = array();
			foreach ($aUrl['query_params'] as $sKey => $sValue) {
				$aPairs[] = $sKey . '=' . urlencode($sValue);
			}
			$sQuery = implode('&', $aPairs);
		} else {
			$sQuery = $aUrl['query'];
		}
		$path = (count($aUrl['path']) ? '/' . Utils::implode('/', $aUrl['path']) : '/');
		if (strpos($path, '.') !== false) {
			$path = substr($path, 0, strlen($path) - 1);
		}
		// Compile url
		$sUrl = (isset($aUrl['scheme']) ? $aUrl['scheme'] . '://' : BASE_URL . '/') . (isset($aUrl['user']) && $aUrl['user'] != '' && isset($aUrl['pass']) ? $aUrl['user'] . ':' . $aUrl['pass'] . '@' : '')
				. (isset($aUrl['host']) ? $aUrl['host'] : '') . $path . ($sQuery != '' ? '?' . $sQuery : '') . (isset($aUrl['fragment']) && $aUrl['fragment'] != '' ? '#' . $aUrl['fragment'] : '');
		return $sUrl;
	}
	public static function addPath($url, $path) {
		return self::add($url, $path, null);
	}
	public static function addParam($url, $param) {
		return self::add($url, null, $param);
	}
	private static function merge($url, $arg, $param) {
		$path = $url['path'];
		$q = $url['query_params'];
		$retval = array();
		$ignore = array(
				'ZDEDebuggerPresent',
				'PHPSESSID');
		switch ($arg) {
		case 'path':
			$param = array_merge($path, $param);
			for ($i = 0; $i < count($param); $i++) {
				if (isset($q[$param[$i]]))
					continue;
				$retval[] = $param[$i];
				if (isset($param[$i + 1])) {
					$retval[] = $param[$i + 1];
				} else {
					$retval[] = null;
				}
				$i++;
			}
			break;
		case 'query_params':
			if (is_string($param)) {
				$p = explode('&', $param);
				$param = array();
				foreach ($p as $v) {
					list($key, $val) = explode('=', $v);
					$param[$key] = $val;
				}
			}
			$q = array_merge($q, $param);
			foreach ($q as $k => $v) {
				$exist = false;
				if (in_array($k, $ignore))
					continue;
				for ($i = 0; $i < count($path); $i += 2) {
					if ($path[$i] == $k) {
						$path[$i] = $v;
						$exist = true;
					}
				}
				if (!$exist) {
					$retval[$k] = $v;
				}
			}
			break;
		default:
			$retval = array_merge($url[$arg], $param);
		}
		return $retval;
	}
	public static function add($url, $path = null, $param = null, $replacer = null) {
		if (!$url) {
			$url = BASE_URL;
		}
		$addparams = null;
		if (is_string($path) && strpos($path, '?') !== false) {
			$addparams = substr($path, strpos($path, '?') + 1);
			$path = substr($path, 0, strpos($path, '?'));
		}
		$url = self::explode($url);
		if ($path) {
			if (!is_array($path)) {
				$path = explode('/', $path);
			}
			$url['path'] = self::merge($url, 'path', $path);
		}
		if ($addparams) {
			$url['query_params'] = self::merge($url, 'query_params', $addparams);
		}
		if ($param) {
			$url['query_params'] = self::merge($url, 'query_params', $param);
		}
		$retval = self::implode($url);
		if ($replacer) {
			$retval =urldecode($retval);
			if ($replacer) {
				foreach ($replacer as $k => $v) {
					$retval = str_replace('#' . $k . '#', $v, $retval);
				}
			}
		}
		return $retval;
	}
	private static function getURLShortenerInstance() {
		static $us;
		if (!$us) {
			$us = new URLShortener();
		}
		return $us;
	}
	public static function shortURL($url) {
		return self::getURLShortenerInstance()->shortUrl($url);
	}
	public static function unshortURL($id) {
		return self::getURLShortenerInstance()->UnshortUrl($id);
	}
}
class URLShortener {
	private $_cachePath;
	private $_urlCache = array();
	private $_urlMap = array();
	function __construct($configs = null) {
		$this->_cachePath = CGAF::getInternalStorage('shorturl', false);
		Utils::makeDir($this->_cachePath);
		if (is_file($this->_cachePath . DS . 'url.map')) {
			$this->_urlMap = unserialize(file_get_contents($this->_cachePath . DS . 'url.map'));
		}
	}
	function __destruct() {
		//ppd($this->_urlMap);
		file_put_contents($this->_cachePath . DS . 'url.map', serialize($this->_urlMap));
		foreach ($this->_urlMap as $k => $v) {
			$fcache = $this->_cachePath . DS . $v;
			file_put_contents($fcache, serialize($this->_urlCache[$v]));
		}
	}
	private function _getById($fid) {
		if (isset($this->_urlCache[$fid])) {
			return $this->_urlCache[$fid];
		}
		$fcache = $this->_cachePath . DS . $fid . '.cache';
		if (is_file($fcache)) {
			$this->_urlCache[$fid] = unserialize(file_get_contents($fcache));
			return $this->_urlCache[$fid];
		}
		return null;
	}
	private function _getCache($url) {
		$surl = URLHelper::explode($url);
		$fid = hash('crc32b', $url);
		return $this->_getById($fid);
	}
	private function _putCache($url, $short) {
		$fid = hash('crc32b', $url);
		$this->_urlCache[$fid] = array(
				'ori' => $url,
				'short' => $short,
				'created' => microtime(true));
		$this->_urlMap[$short] = $fid;
		return $this->_urlCache[$fid];
	}
	//source : https://github.com/briancray/PHP-URL-Shortener.git
	private function getShortenedURLFromID($url, $base = ALLOWED_CHARS) {
		$length = strlen($base);
		$out = '';
		$integer = rand(100, microtime(true));
		while ($integer > $length - 1) {
			$out = $base[fmod($integer, $length)] . $out;
			$integer = floor($integer / $length);
		}
		return $out;
	}
	function shortURL($url, $full = false) {
		$retval = $this->_getCache($url);
		if (!$retval) {
			$retval = $this->_putCache($url, self::getShortenedURLFromID($url));
			//pp($this->_urlMap);
		}
		return $full ? $retval : $retval['short'];
	}
	function UnshortUrl($id, $full = false) {
		if (!isset($this->_urlMap[$id])) {
			return null;
		}
		$uid = $this->_urlMap[$id];
		$retval = $this->_getById($this->_urlMap[$id]);
		if (!$retval) {
			return null;
		}
		return $full ? $retval : $retval['ori'];
	}
}
/*
 for($i = 0; $i <= 1000; $i ++) {
$mt = microtime ( true );
$url = 'http://www.google.com/?q=' . $i;
$short = URLHelper::shortURL ( $url );
$us = URLHelper::unshortURL ( $short );
$e = microtime ( true );
pp ( $i . $url . '->' . $short . '->' . $us . '..... ' . round ( $e - $mt, 4 ) . 'ms' );
}
ppd ( 'x' );*/

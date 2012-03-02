<?php
namespace System\Web;
use Utils;
class ClientInfo {
	const DEFAULT_AGENT = 'Mozilla/5.0 (X11; Linux i686) AppleWebKit/535.11 (KHTML, like Gecko) Ubuntu/11.10 Chromium/17.0.963.12 Chrome/17.0.963.12 Safari/535.11';
	private $_path;
	private $_cachepath;
	private $_data;
	function __construct($path,$cachePath) {
		// throw new Exception($path);
		$this->_path = Utils::toDirectory ( $path . DS );
		$this->_cachepath = $cachePath;
		$this->_data = $this->parse ();
	}
	function __get($name) {
		if (isset ( $this->_data->$name )) {
			return $this->_data->$name;
		}
	}
	function isMobile() {
		$pl = array (
				'ipad'
		);
		return \Convert::toBool ( $this->_data->ismobiledevice ) === true || in_array ( $this->_data->platform, $pl );
	}
	private function getBrowser($u_agent = null) {
		$u_agent = $u_agent ? $u_agent : (isset ( $_SERVER ['HTTP_USER_AGENT'] ) ? $_SERVER ['HTTP_USER_AGENT'] : self::DEFAULT_AGENT);
		$bname = 'Unknown';
		$platform = 'Unknown';
		$version = "";
		$pattern = null;
		$mobile = null;
		$verpatern = '[/ ]+(?<version>[0-9.|a-zA-Z.]*)';
		if (preg_match ( '/mobile/i', $u_agent )) {
			$mobile = true;
		}
		// First get the platform?
		if (preg_match ( '/ipad/i', $u_agent )) {
			$mobile = true;
			$platform = 'ipad';
		} elseif (preg_match ( '/linux/i', $u_agent )) {
			$platform = 'linux';
		} elseif (preg_match ( '/macintosh|mac os x/i', $u_agent )) {
			$platform = 'mac';
		} elseif (preg_match ( '/windows|win32/i', $u_agent )) {
			$platform = 'windows';
		}
		// Next get the name of the useragent yes seperately and for good reason
		if (preg_match ( '/MSIE/i', $u_agent ) && ! preg_match ( '/Opera/i', $u_agent )) {
			$bname = 'Internet Explorer';
			$ub = "MSIE";
		} elseif (preg_match ( '/Firefox/i', $u_agent )) {
			$bname = 'Mozilla Firefox';
			$ub = "Firefox";
			if ($platform==='linux') {
				$ub='firefox';
				$verpatern = '[/]+(?<version>[0-9.|a-zA-Z.]*)';
			}
		} elseif (preg_match ( '/Chromium/i', $u_agent )) {
			$pattern = '#(?<browser>chromium)[/ ]+(?<version>[0-9.|a-zA-Z.]*)#';
			$bname = 'Chromium';
			$ub = "Chromium";
		} elseif (preg_match ( '/Chrome/i', $u_agent )) {
			$bname = 'Google Chrome';
			$ub = "Chrome";
		} elseif (preg_match ( '/Safari/i', $u_agent )) {
			$bname = 'Apple Safari';
			$ub = "Safari";
		} elseif (preg_match ( '/Opera/i', $u_agent )) {
			$bname = 'Opera';
			$ub = "Opera";
		} elseif (preg_match ( '/Netscape/i', $u_agent )) {
			$bname = 'Netscape';
			$ub = "Netscape";
		}
		// finally get the correct version number
		$known = array (
				'Version',
				$ub,
				'other'
		);
		if (! $pattern) {
			$pattern = '#(?<browser>' . join ( '|', $known ) . ')' . $verpatern . '#';
		}
		if (! @preg_match_all ( $pattern, $u_agent, $matches )) {
			// we have no matching number just continue
		}
		// see how many we have
		$i = count ( $matches ['browser'] );
		if ($i != 1) {
			// we will have two since we are not using 'other' argument yet
			// see if version is before or after the name
			if (strripos ( $u_agent, "Version" ) < strripos ( $u_agent, $ub )) {
				$version = @$matches ['version'] [0];
			} else {
				$version = @$matches ['version'] [1];
			}
		} else {
			$version = $matches ['version'] [0];
		}
		// check if we have a number
		if ($version == null || $version == "") {
			$version = "?";
		}
		return array (
				'userAgent' => $u_agent,
				'name' => $bname,
				'mobile' => $mobile,
				'realName' => $ub,
				'version' => $version,
				'platform' => $platform,
				'pattern' => $pattern
		);
	}
	function parse($agent = null) {
		$agent = strtolower ( $agent ? $agent : isset ( $_SERVER ['HTTP_USER_AGENT'] ) ? $_SERVER ['HTTP_USER_AGENT'] : self::DEFAULT_AGENT );
		$cfile='';		
		$cfile = $this->_cachepath . crc32 ( $agent );
		$retval = null;
		//unlink($cfile);
		if (is_file ( $cfile )) {
			$retval = unserialize ( file_get_contents ( $cfile ) );
		}
		if (! $retval) {
			$matches = $this->getBrowser ( $agent );
			$retval = $this->getBrowserCaps ( $matches ['realName'], $matches ['version'],$matches ['platform'], $agent );
			if ($matches ['mobile'] != null) {
				$retval->ismobiledevice = $matches ['mobile'];
			}
			file_put_contents ( $cfile, serialize ( $retval ) );
		}
		return $retval;
	}
	function setPlatform($value) {
		$this->_data->platform = $value;
	}
	private function getBrowserCaps($name, $version, $platform,$agent) {
		if (! is_array ( $name )) {
			$name = array (
					$name
			);
		}
		$retval = new TClientInfoData ();
		$retval->agent = $agent;
		$retval->browser = $name;
		$retval->version = $version;
		$retval->platform = $platform;
		$this->parseFile ( $this->_path . 'default.ini', $retval );
		foreach ( $name as $k => $v ) {
			$ver = explode ( '.', $version [$k] );
			$fname = $this->_path . strtolower ( $name [$k] ) . '.ini';
			$this->parseFile ( $fname, $retval );
			$fname = $this->_path . strtolower ( $name [$k] ) .DS.$platform. '.ini';
			$this->parseFile ( $fname, $retval );
			$fname = $this->_path . strtolower ( $name [$k] ) . DS;
			foreach ( $ver as $ve ) {
				$fname .= $ve . ".";
				$this->parseFile ( $fname . 'ini', $retval );
			}
		}
		return $retval;
	}
	private function parseFile($f, &$o) {
		if (is_file ( $f )) {
			$config = Utils::parseIni ( $f );
			if (isset ( $config ['Default'] )) {
				$o->assign ( $config ['Default'] );
			}
		}
	}
	function isIE() {
		return stripos ( $this->get ( 'agent', '' ), 'msie' ) > 0;
	}
	public function get($key, $def) {
		if (! $this->_data) {
			$this->_data = $this->parse ();
		}
		if (! is_object ( $this->_data )) {
			return $def;
		}
		return $this->_data->get ( $key, $def );
	}
}
class TClientInfoData {
	public $browser;
	public $version;
	function __get($k) {
		$k = strtolower ( $k );
		if (isset ( $this->$k )) {
			return $this->$k;
		}
		pp ( $k );
		ppd ( $this );
	}
	function __set($key,$val) {
		$key = strtolower($key);
		$this->$key=$val;
	}
	function get($key, $def) {
		if (isset ( $this->$key ) && $this->$key !== null) {
			return $this->$key;
		}
		return $def;
	}
	function assign($o) {
		foreach ( $o as $k => $v ) {
			$k = strtolower ( $k );
			$this->$k = $v;
		}
	}
}

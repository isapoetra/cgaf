<?php
namespace System\Web;
use \Utils;
class ClientInfo {
	private $_path;
	private $_cachepath;
	private $_data;
	function __construct($path) {
		//throw new Exception($path);
		$this->_path = Utils::toDirectory($path . DS);
		$this->_cachepath = Utils::makeDir($path . DS . '.cache/', 0777, '*');
		$this->_data = $this->parse();
	}
	private function getBrowser($u_agent = null) {
		$u_agent = $u_agent ? $u_agent : $_SERVER['HTTP_USER_AGENT'];
		$bname = 'Unknown';
		$platform = 'Unknown';
		$version = "";
		$pattern = null;
		$verpatern = '[/ ]+(?<version>[0-9.|a-zA-Z.]*)';
		//First get the platform?
		if (preg_match('/linux/i', $u_agent)) {
			$platform = 'linux';
		} elseif (preg_match('/macintosh|mac os x/i', $u_agent)) {
			$platform = 'mac';
		} elseif (preg_match('/windows|win32/i', $u_agent)) {
			$platform = 'windows';
		}
		// Next get the name of the useragent yes seperately and for good reason
		if (preg_match('/MSIE/i', $u_agent) && !preg_match('/Opera/i', $u_agent)) {
			$bname = 'Internet Explorer';
			$ub = "MSIE";
		} elseif (preg_match('/Firefox/i', $u_agent)) {
			$bname = 'Mozilla Firefox';
			$ub = "Firefox";
		} elseif (preg_match('/Chromium/i', $u_agent)) {
			$pattern = '#(?<browser>chromium)[/ ]+(?<version>[0-9.|a-zA-Z.]*)#';
			$bname = 'Chromium';
			$ub = "Chromium";
		} elseif (preg_match('/Chrome/i', $u_agent)) {
			$bname = 'Google Chrome';
			$ub = "Chrome";
		} elseif (preg_match('/Safari/i', $u_agent)) {
			$bname = 'Apple Safari';
			$ub = "Safari";
		} elseif (preg_match('/Opera/i', $u_agent)) {
			$bname = 'Opera';
			$ub = "Opera";
		} elseif (preg_match('/Netscape/i', $u_agent)) {
			$bname = 'Netscape';
			$ub = "Netscape";
		}
		// finally get the correct version number
		$known = array(
				'Version',
				$ub,
				'other');
		if (!$pattern) {
			$pattern = '#(?<browser>' . join('|', $known) . ')' . $verpatern . '#';
		}
		if (!@preg_match_all($pattern, $u_agent, $matches)) {
			// we have no matching number just continue
		}
		// see how many we have
		$i = count($matches['browser']);
		if ($i != 1) {
			//we will have two since we are not using 'other' argument yet
			//see if version is before or after the name
			if (strripos($u_agent, "Version") < strripos($u_agent, $ub)) {
				$version = @$matches['version'][0];
			} else {
				$version = @$matches['version'][1];
			}
		} else {
			$version = $matches['version'][0];
		}
		// check if we have a number
		if ($version == null || $version == "") {
			$version = "?";
		}
		return array(
				'userAgent' => $u_agent,
				'name' => $bname,
				'realName' => $ub,
				'version' => $version,
				'platform' => $platform,
				'pattern' => $pattern);
	}
	function parse($agent = null) {
		$agent = strtolower($agent ? $agent : $_SERVER['HTTP_USER_AGENT']);
		$cfile = $this->_cachepath . crc32($agent);
		$retval = null;
		if (is_file($cfile)) {
			if (CGAF_DEBUG) {
				unlink($cfile);
			} else {
				$retval = unserialize(file_get_contents($cfile));
			}
		}
		if (!$retval) {
			$matches = $this->getBrowser($agent);
			$retval = $this->getBrowserCaps($matches['realName'], $matches['version'], $agent);
			file_put_contents($cfile, serialize($retval));
		}
		return $retval;
	}
	private function getBrowserCaps($name, $version, $agent) {
		if (!is_array($name)) {
			$name = array(
					$name);
		}
		$retval = new TClientInfoData();
		$retval->agent = $agent;
		$retval->Browser = $name;
		$retval->version = $version;
		$this->parseFile($this->_path . 'default.ini', $retval);
		foreach ($name as $k => $v) {
			$ver = explode('.', $version[$k]);
			$fname = $this->_path . strtolower($name[$k]) . '.ini';
			$this->parseFile($fname, $retval);
			$fname = $this->_path . strtolower($name[$k]) . DS;
			foreach ($ver as $ve) {
				$fname .= $ve . ".";
				$this->parseFile($fname . 'ini', $retval);
			}
		}
		return $retval;
	}
	private function parseFile($f, &$o) {
		if (is_file($f)) {
			$f = Utils::parseIni($f);
			$o->assign($f['Default']);
		}
	}
	function isIE() {
		return stripos($this->get('agent', ''), 'msie') > 0;
	}
	public function get($key, $def) {
		if (!$this->_data) {
			$this->_data = $this->parse();
		}
		if (!is_object($this->_data)) {
			return $def;
		}
		return $this->_data->get($key, $def);
	}
}
class TClientInfoData {
	public $browser;
	public $version;
	function __get($k) {
		$k = strtolower($k);
		if (isset($this->$k)) {
			return $this->$k;
		}
		pp($k);
		ppd($this);
	}
	function get($key, $def) {
		if (isset($this->$key) && $this->$key !== null) {
			return $this->$key;
		}
		return $def;
	}
	function assign($o) {
		foreach ($o as $k => $v) {
			$k = strtolower($k);
			$this->$k = $v;
		}
	}
}

<?php
namespace System\Web;
use \Utils;
class ClientInfo {
	private $_path;
	private $_cachepath;
	private $_data;
	private $_desktopPlatform = array(
			'linux' => 'linux',
			'macintosh' => 'macintosh|mac os x',
			'windows' => 'windows|win32');
	//some code are based on http://code.google.com/p/php-mobile-detect/source/browse/trunk/Mobile_Detect.php
	private $_mobilePlatform = array(
			"android" => "android.*mobile",
			"androidtablet" => "android(?!.*mobile)",
			"blackberry" => "blackberry",
			"blackberrytablet" => "rim tablet os",
			"iphone" => "(iphone|ipod)",
			"ipad" => "(ipad)",
			"palm" => "(avantgo|blazer|elaine|hiptop|palm|plucker|xiino)",
			"windows" => "windows ce; (iemobile|ppc|smartphone)",
			"windowsphone" => "windows phone os",
			"generic" => "(kindle|mobile|mmp|midp|o2|pda|pocket|psp|symbian|smartphone|treo|up.browser|up.link|vodafone|wap|opera mini)");
	function __construct($path) {
		//throw new Exception($path);
		$this->_path = Utils::toDirectory($path . DS);
		$this->_cachepath = Utils::makeDir($path . DS . '.cache/', 0770, '*');
		$this->_data = $this->parse();
		$this->_userAgent = $_SERVER['HTTP_USER_AGENT'];
		$this->_accept = isset($_SERVER['HTTP_ACCEPT']) ? $_SERVER['HTTP_ACCEPT'] : null;
	}
	private function getBrowser($u_agent = null, $accept = null) {
		$u_agent = $agent = strtolower($u_agent ? $u_agent : $_SERVER['HTTP_USER_AGENT']);
		$accept = strtolower($accept ? $accept : $_SERVER['HTTP_ACCEPT']);
		$bname = 'Unknown';
		$platform = 'Unknown';
		$version = "";
		$pattern = null;
		$known = array(
				'msie',
				'firefox',
				'safari',
				'webkit',
				'opera',
				'netscape',
				'konqueror',
				'gecko');
		$pattern = '#(?<browser>' . join('|', $known) . ')[/ ]+(?<version>[0-9]+(?:\.[0-9]+)?)#';
		// Find all phrases (or return empty array if none found)
		if (!preg_match_all($pattern, $agent, $matches))
			return array();
		$mobile = false;
		foreach ($this->_desktopPlatform as $k => $v) {
			if (preg_match('/' . $v . '/i', $u_agent)) {
				$platform = $k;
				$mobile = false;
				break;
			}
		}
		$accept = $_SERVER['HTTP_ACCEPT'];
		if (isset($_SERVER['HTTP_X_WAP_PROFILE']) || isset($_SERVER['HTTP_PROFILE'])) {
			$mobile = true;
		} elseif (strpos($accept, 'text/vnd.wap.wml') > 0 || strpos($accept, 'application/vnd.wap.xhtml+xml') > 0) {
			$mobile = true;
		}
		foreach ($this->_mobilePlatform as $k => $v) {
			if (preg_match('/' . $v . '/i', $u_agent)) {
				$platform = $k;
				$mobile = true;
				break;
			}
		}
		$i = count($matches['browser']) - 1;
		$retval = array(
				'userAgent' => $agent,
				'platform' => $platform,
				'mobiledevice' => $mobile,
				'name' => $matches['browser'],
				'version' => $matches['version']);
		return $retval;
	}
	function parse($agent = null) {
		$agent = strtolower($agent ? $agent : $_SERVER['HTTP_USER_AGENT']);
		$b = new Browscap($this->_cachepath);
		$b->updateMethod = Browscap::UPDATE_LOCAL;
		$b->localFile = $this->_path . 'browscap.ini';
		$b->doAutoUpdate = true;
		return $b->getBrowser($agent, true);
		/*$cfile = $this->_cachepath . crc32($agent);
		$retval = null;
		if (is_file($cfile)) {
		    //$retval = unserialize(file_get_contents($cfile));
		}
		if (!$retval) {
		    //$agent = 'mozilla/5.0 (linux; u; android 4.0.1; en-us; sdk build/ics_mr0) applewebkit/534.30 (khtml, like gecko) version/4.0 mobile safari/534.30</span>0,mozilla/5.0 (linux; u; android 4.0.1; en-us; sdk build/ics_mr0) applewebkit/534.30 (khtml, like gecko) version/4.0 mobile safari/534.30';
		    $matches = $this->getBrowser($agent);
		    $retval = $this->getBrowserCaps($matches['platform'], $matches['name'], $matches['version'], $agent);
		    file_put_contents($cfile, serialize($retval));
		}
		return $retval;*/
	}
	private function getBrowserCaps($platform, $name, $version, $agent) {
		if (!is_array($name)) {
			$name = array(
					$name);
		}
		if (!is_array($version)) {
			$version = array(
					$version);
		}
		$retval = new TClientInfoData();
		$retval->agent = $agent;
		$retval->browser = $name;
		$retval->version = $version;
		$retval->platform = $platform;
		$ismobile = false;
		$this->parseFile($this->_path . 'default.ini', $retval);
		$this->parseFile($this->_path . 'default-' . $platform . '.ini', $retval);
		foreach ($name as $k => $v) {
			$ver = explode('.', $version[$k]);
			$fname = $this->_path . strtolower($name[$k]) . '.ini';
			$this->parseFile($fname, $retval);
			$fname = $this->_path . strtolower($name[$k]) . '-' . $platform . '.ini';
			$this->parseFile($fname, $retval);
			$fname = $this->_path . strtolower($name[$k]) . DS;
			foreach ($ver as $ve) {
				$fname .= $ve . ".";
				$this->parseFile($fname . 'ini', $retval);
				$this->parseFile($fname . $platform . '.ini', $retval);
			}
		}
		return $retval;
	}
	protected function isDevice($device) {
		$var = "is" . ucfirst($device);
		$return = $this->$var === null ? (bool) preg_match("/" . $this->devices[strtolower($device)] . "/i", $this->userAgent) : $this->$var;
		if ($device != 'generic' && $return == true) {
			$this->isGeneric = false;
		}
		return $return;
	}
	private function parseFile($f, &$o) {
		static $parsed;
		if (isset($parsed[$f]))
			return;
		$parsed[$f] = true;
		if (is_file($f)) {
			$f = Utils::parseIni($f);
			if (isset($f['Default'])) {
				$o->assign($f['Default']);
			}
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
	public function isMobile() {
		return $this->_data['isMobileDevice'];
	}
	public function setPlatform($value) {
		$this->_data['Platform'] = $value;
	}
	public function getPlatform() {
		return $this->_data['Platform'];
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
	}
	function get($key, $def) {
		if (isset($this->$key) && $this->$key !== null) {
			return $this->$key;
		}
		return $def;
	}
	function assign($o, $value = null) {
		if (is_array($o) || is_object($o)) {
			foreach ($o as $k => $v) {
				$k = strtolower($k);
				$this->$k = $v;
			}
		} elseif ($value !== null) {
			$this->$o = $value;
		}
	}
}

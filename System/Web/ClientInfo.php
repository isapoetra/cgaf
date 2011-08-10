<?php
namespace System\Web;
use \Utils;
class ClientInfo {
	private $_path;
	private $_cachepath;
	private $_data;

	function __construct($path) {
		//throw new Exception($path);
		

		$this->_path = Utils::toDirectory ( $path . DS );
		$this->_cachepath = Utils::makeDir ( $path . DS . '.cache/', 0777, '*' );
		$this->_data = $this->parse ();
	}

	function parse($agent = null) {
		// Declare known browsers to look for
		$known = array (
				"firefox", 
				"msie", 
				"opera", 
				"chrome", 
				"safari", 
				"mozilla", 
				"seamonkey", 
				"konqueror", 
				"netscape", 
				"gecko", 
				"navigator", 
				"mosaic", 
				"lynx", 
				"amaya", 
				"omniweb", 
				"avant", 
				"camino", 
				"flock", 
				"aol" );
		
		// Clean up agent and build regex that matches phrases for known browsers
		// (e.g. "Firefox/2.0" or "MSIE 6.0" (This only matches the major and minor
		// version numbers.  E.g. "2.0.0.6" is parsed as simply "2.0"
		$agent = strtolower ( $agent ? $agent : $_SERVER ['HTTP_USER_AGENT'] );
		
		$cfile = $this->_cachepath . crc32 ( $agent );
		$retval = null;
		if (is_file ( $cfile )) {
			if (CGAF_DEBUG) {
				unlink ( $cfile );
			} else {
				$retval = unserialize ( file_get_contents ( $cfile ) );
			}
		}
		if (! $retval) {
			$pattern = '#(?<browser>' . join ( '|', $known ) . ')[/ ]+(?<version>([0-9.]*))#';
			// Find all phrases (or return empty array if none found)
			if (! preg_match_all ( $pattern, $agent, $matches )) {
				return array ();
			}
			
			$retval = $this->getBrowserCaps ( $matches ['browser'], $matches ['version'], $agent );
			file_put_contents ( $cfile, serialize ( $retval ) );
		}
		
		return $retval;
	}

	private function getBrowserCaps($name, $version, $agent) {
		$retval = new TClientInfoData ();
		$retval->agent = $agent;
		$retval->Browser = $name;
		$retval->version = $version;
		$this->parseFile ( $this->_path . 'default.ini', $retval );
		
		foreach ( $name as $k => $v ) {
			$ver = explode ( '.', $version [$k] );
			$fname = $this->_path . $name [$k] . '.ini';
			$this->parseFile ( $fname, $retval );
			$fname = $this->_path . $name [$k] . DS;
			foreach ( $ver as $ve ) {
				$fname .= $ve . ".";
				
				$this->parseFile ( $fname . 'ini', $retval );
			}
		}
		
		return $retval;
	}

	private function parseFile($f, &$o) {
		if (is_file ( $f )) {
			$f = Utils::parseIni ( $f );
			$o->assign ( $f ['Default'] );
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
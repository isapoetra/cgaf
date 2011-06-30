<?php
if (! defined ( "CGAF" ))
	die ( "Restricted Access" );

/**
 * Enter description here ...
 * @author e1
 *
 */
class Configuration extends Object implements IConfiguration, IRenderable {
	const NL = "\n";
	protected $_configs = array ();
	private $_useDef = true;
	private $_configCache = array ();
	private $_configFile = null;
	private $_parser;
	function __construct($configs = null, $useDefault = true) {
		$this->_useDef = $useDefault;
		$this->setConfigs ( $configs );
	}
	public function setConfigFile($value) {
		$this->_configFile = $value;
	}
	public function Save($fileName = null,$settings=NULL) {
		
		if (! $fileName && ! $this->_configFile) {
			throw new Exception ( "Unable to find Config File", 505 );
		}
		
		if (! $fileName) {
			$fileName = $this->_configFile;
		}
		$parser = $this->getParser ( Utils::getFileExt ( $fileName, false ) );
		$parser->save ( $fileName, $this ,$settings ? $settings : $this->_configs);
	
	}
	
	public function setConfigs($configs) {
		$configs = $configs ? $configs : array ();
		if (count ( $configs ) == 0 && $this->_useDef) {
			$configs = array (
				"System.DataPath" => Utils::ToDirectory ( CGAF_PATH . DS . "Data" ) );
		
		}
		foreach ( $configs as $k => $v ) {
			$this->setConfig ( $k, $v );
		}
	}
	public function clear() {
		$this->_configs = array ();
		if ($this->_useDef) {
			$this->setConfig ( "System.DataPath", Utils::ToDirectory ( CGAF_PATH . DS . "Data" ) );		
		}
	}
	public function getConfigGroups() {
		return array_keys ( $this->_configs );
	}
	private function _setConfig(&$configs, $key, $value, $prev) {
		$cfgs = explode ( '.', $key );
		$k = array_shift ( $cfgs );
		
		if (count ( $cfgs )) {
			if (! isset ( $configs [$k] ) || ! is_array ( $configs [$k] )) {
				$configs [$k] = array ();
			}
			$this->_setConfig ( $configs [$k], implode ( $cfgs, '.' ), $value, $prev . '.' . $k );
		} elseif ($value !== '___unset') {
			$configs [$k] = $value;
		}else{
			unset($configs[$k]);
		}
	}
	
	public function setConfig($configName, $value = null) {
		if (is_array ( $configName )) {
			return $this->Merge ( $configName, true );
		}
		if (strpos ( $configName, "." ) == 0 && ! is_array ( $value )) {
			$configName = "System.$configName";
		}
		
		$cfgs = explode ( '.', $configName );
		$k = array_shift ( $cfgs );
		if (count ( $cfgs )) {
			if (! isset ( $this->_configs [$k] ) || ! is_array ( $this->_configs [$k] )) {
				$this->_configs [$k] = array ();
			}
			
			$this->_setConfig ( $this->_configs [$k], implode ( $cfgs, '.' ), $value, $k );
		} else {
			$pr = $this->getConfig ( $configName );
			if ($pr) {
				if (is_array ( $value )) {
					if (isset ( $this->_configs [$k] ) && is_array ( $this->_configs [$k] )) {
						$value = array_merge ( $this->_configs [$k], $value );
					}
				}
				
				$this->_setConfig ( $this->_configs, $configName, $value, $k );
			} else {
				$this->_configs [$k] = $value;
			}
		}
	}
	public function assign($var, $val=null) {
		if (! is_array ( $var )) {
			$this->Merge ( array (
				$var => $val ), true );
		} else {
			$this->Merge ( $var, true );
		}
		return $this;
	}
	function remove($name) {
		$this->setConfig($name,'___unset');
	}
	public function Merge($_configs, $overwrite = false) {
		if (is_array ( $_configs )) {
			foreach ( $_configs as $k => $v ) {
				if (is_array ( $v ) && ! $overwrite) {
					
					$x = array_keys ( $v );
					foreach ( $x as $vv ) {
						$this->setConfig ( $k . ".$vv", $_configs [$k] [$vv] );
					}
				} else {
					$this->setConfig ( $k, $_configs [$k] );
				}
			}
		}
	}
	
	public function getConfig($configName, $default = null) {
		if (isset ( $this->_configCache [$configName] )) {
			return $this->_configCache [$configName];
		}
		$retval = Utils::findConfig ( 'System.' . $configName, $this->_configs );
		
		if ($retval === null) {
			$retval = Utils::findConfig ( $configName, $this->_configs );
		}
		//if ($retval !== null) {
		$this->_configCache [$configName] = $retval;
		//}
		return $retval === null ? $default : $retval;
	}
	public function getConfigs($prefix = null) {
		if ($prefix == null) {
			return $this->_configs;
		}
		$r = array ();
		if (isset ( $this->_configs [$prefix] )) {
			return $this->_configs [$prefix];
		}
		
		$nprefix = $prefix ? $prefix . "." : $prefix;
		return Utils::findConfig ( $prefix, $this->_configs );
	}
	private function findConfigFile($f) {
		if (is_file ( $f )) {
			return $f;
		}
		
		if (is_file ( $f = Utils::changeFileExt ( $f, 'php' ) )) {
			return $f;
		} elseif (is_file ( $f = Utils::changeFileExt ( $f, 'ini' ) )) {
			return $f;
		} elseif (is_file ( $f = Utils::changeFileExt ( $f, 'config' ) )) {
			return $f;
		} elseif (is_file ( $f = Utils::changeFileExt ( $f, 'xml' ) )) {
			return $f;
		}
		return null;
	}
	public function getParser($ext) {
		if (isset ( $this->_parser [$ext] )) {
			return $this->_parser [$ext];
		}
		$parser = null;
		switch (strtolower ( $ext )) {
			case 'manifest' :
			case 'gaf' :
				$ext = 'xml';
				break;
		}
		if (CGAF::using ( 'System.Configuration.Parsers.' . $ext, false )) {
			$c = CGAF_CLASS_PREFIX . 'ConfigParser' . $ext;
			$this->_parser [$ext] = new $c ();
		}
		return $this->_parser [$ext];
	}
	public function loadFile($f) {
		$f = $this->findConfigFile ( $f );
		if (! $f) {
			return false;
		}
		$this->_configFile = $f;
		$ext = Utils::getFileExt ( $f, false );
		$parser = $this->getParser ( $ext );
		$configs = null;
		if ($parser) {
			$configs = $parser->parseFile ( $f );
		}
		if ($configs) {
			$this->merge ( $configs );
		}
		return true;
	}
	
	
	/**
	 * @param unknown_type $return
	 */
	public function Render($return = false, &$handle = false) {
		$arr = array ();
		foreach ( $this->_configs as $k => $v ) {
			if ($k === 'System') {
				foreach ( $v as $kk => $vv ) {
					$arr [$kk] = $vv;
				}
			} else {
				$arr [$k] = $v;
			}
		}
		return $arr;
	}

}

final class Configurationx {
	private static $_initialized;
	private static $_instance;
	
	public static function Init($configs) {
		global $_configs;
		if (self::$_initialized) {
			return;
		}
		
		if ($configs == null) {
			if ($_configs === null) {
				include CGAF_PATH . "config.php";
			}
			$configs = $_configs;
		}
		self::$_instance = new Configuration ( $configs );
		self::$_initialized = true;
		unset ( $_configs );
	}
	
	public static function getInstance() {
		if (self::$_instance == null) {
			self::Init ( null );
		}
		return self::$_instance;
	}
	
	public static function Merge($_configs) {
		return self::getInstance ()->Merge ( $_configs );
	}
	
	public static function getConfig($configName, $default = null) {
		return self::getInstance ()->getConfig ( $configName, $default );
	}
	public static function getConfigs($prefix = null) {
		return self::getInstance ()->getConfigs ( $prefix );
	}
	public static function setConfig($configName, $configValue) {
		return self::getInstance ()->setConfig ( $configName, $configValue );
	}
}
?>

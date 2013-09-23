<?php
namespace System\Configurations;
use CGAF;
use System\Exceptions\SystemException;
use Utils;

/**
 * @author Iwan Sapoetra
 * TODO Optimize!!!!  Convert::toArray slow and performance hits  to huge,,, cache ?
 */
class Configuration extends \BaseObject implements IConfiguration, \IRenderable
{
    const NL = PHP_EOL;
    protected $_configs = array();
    private $_useDef = true;
    private $_configCache = array();
    private $_configFile = null;
    private $_parser;
    private $_dirty = false;
    protected $_debug = false;

    function __construct($configs = null, $useDefault = true)
    {
        $this->_useDef = $useDefault;
        $this->setConfigs($configs);
    }

    function __destruct()
    {
        if ($this->_dirty && $this->_configFile) {
            file_put_contents($this->getCacheFile(), serialize(array(
                        'mtime' => filemtime($this->_configFile),
                        'configs' => $this->_configs,
                        'cached' => $this->_configCache)
                )
            );
        }
        parent::__destruct();
    }

    public function setConfigFile($value)
    {
        $this->_configFile = $value;
    }

    public function setDirty($value)
    {
        $this->_dirty = $value;
    }

    public function Save($fileName = null, $settings = NULL)
    {
        if ($this->_dirty === false) {
            return true;
        }
        if (!$fileName && !$this->_configFile) {
            throw new \Exception ("Unable to find Config File", 505);
        }
        if (!$fileName) {
            $fileName = $this->_configFile;
        }
        $parser = $this->getParser(Utils::getFileExt($fileName, false));
        if ($this->_dirty && is_file($fileName) && !is_writable($fileName)) {
            \Logger::Warning($fileName . ' Not Writable');
            return false;
        }
        if ($parser->save($fileName, $this, $settings ? $settings : $this->_configs)) {
            $this->_dirty = false;
        }
        return true;
    }

    public function setConfigs($configs)
    {
        $configs = $configs ? $configs : array();
        if (count($configs) == 0 && $this->_useDef) {
            $configs = array(
                "System.DataPath" => Utils::ToDirectory(CGAF_PATH . DS . "Data")
            );
        }
        foreach ($configs as $k => $v) {
            $this->setConfig($k, $v);
        }
    }

    public function clear()
    {
        $this->_configs = array();
        if ($this->_useDef) {
            $this->setConfig("System.DataPath", Utils::ToDirectory(CGAF_PATH . DS . "Data"));
        }
    }

    public function getConfigGroups()
    {
        return array_keys($this->_configs);
    }

    private function _setConfig(&$configs, $key, $value, $prev)
    {
        $cfgs = explode('.', $key);
        $k = array_shift($cfgs);
        if (count($cfgs)) {
            if (!isset ($configs [$k]) || !is_array($configs [$k])) {
                $configs [$k] = array();
            }
            $this->_setConfig($configs [$k], implode($cfgs, '.'), $value, $prev . '.' . $k);
        } elseif ($value !== '___unset' && $value !== null) {
            if (!isset ($configs [$k]) || $configs [$k] !== $value) {
                $this->_dirty = true;
            }
            $configs [$k] = $value;
        } else {
            unset ($configs [$k]);
        }
    }

    /**
     * @param $configName
     * @return bool
     */
    protected function _canSetConfig(/** @noinspection PhpUnusedParameterInspection */
        $configName)
    {
        return true;
    }

    public function setConfig($configName, $value = null)
    {
        $ori = $configName;
        if (is_array($configName)) {
            return $this->Merge($configName, true);
        }
        if (strpos($configName, ".") == 0 && !is_array($value)) {
            $configName = "System.$configName";
        }
        if (!$this->_canSetConfig($configName)) {
            return null;
        }
        $cfgs = explode('.', $configName);
        $k = array_shift($cfgs);
        if (count($cfgs)) {
            if (!isset ($this->_configs [$k]) || !is_array($this->_configs [$k])) {
                $this->_configs [$k] = array();
            }
            $this->_setConfig($this->_configs [$k], implode($cfgs, '.'), $value, $k);
        } else {
            $pr = $this->getConfig($configName);
            if ($pr) {
                if (is_array($value)) {
                    if (isset ($this->_configs [$k]) && is_array($this->_configs [$k])) {
                        $value = array_merge($this->_configs [$k], $value);
                    }
                }
                $this->_setConfig($this->_configs, $configName, $value, $k);
            } else {
                if (!isset ($this->_configs [$k]) || $this->_configs [$k] !== $value) {
                    $this->_dirty = true;
                }
                $this->_configs [$k] = $value;
            }
        }
        $this->_configCache [$ori] = $value;
        return $value;
    }

    public function assign($var, $val = null)
    {
        if (!is_array($var)) {
            $this->Merge(array(
                $var => $val
            ), true);
        } else {
            $this->Merge($var, true);
        }
        return $this;
    }

    function remove($name)
    {
        $this->setConfig($name, '___unset');
    }

    public function Merge($_configs, $overwrite = false)
    {
        if (is_array($_configs) || is_object($_configs)) {
            foreach ($_configs as $k => $v) {
                if (is_object($v) && !$overwrite) {
                    foreach ($v as $kk => $vv) {
                        $this->setConfig($k . ".$kk", $vv);
                    }
                } elseif (is_array($v) && !$overwrite) {
                    $x = array_keys($v);
                    foreach ($x as $vv) {
                        if (is_numeric($vv)) {
                            $this->setConfig($k, $v);

                        } elseif (is_object($_configs)) {
                            $this->setConfig($k . ".$vv", $_configs->$k = $vv);
                        } else {
                            $this->setConfig($k . ".$vv", $_configs [$k] [$vv]);
                        }
                    }
                } else {
                    $this->setConfig($k, $v);
                }
            }
            $this->_configCache = array();
            $this->_configs = \Convert::toArray($this->reparseConfig($this->_configs));
        }
        return null;
    }

    private function reparseConfig(&$config)
    {
        if (is_array($config) || is_object($config)) {
            foreach ($config as $k => $v) {
                if (is_array($config)) {
                    $config [$k] = $this->reparseConfig($v);
                } else {
                    $config->$k = $this->reparseConfig($v);
                }
            }
            return $config;
        }
        $matches = array();
        preg_match_all('|\${(.*)}|U', $config, $matches, PREG_PATTERN_ORDER);
        if ($matches && isset ($matches [0] [0])) {
            $keys = $matches [0];
            foreach ($keys as $k => $v) {
                $value = $this->getConfig($matches [1] [$k]);
                $config = str_ireplace($v, $value, $config);
            }
        }
        return $config;
    }

    public function getConfig($configName, $default = null)
    {
        if (array_key_exists ($configName,$this->_configCache)) {
            if ($this->_configCache[$configName] ===null && $default !==null) {
                $this->_configCache[$configName] = $default;
                $this->_dirty =true;
            }
            return $this->_configCache[$configName];
        }
        if (!$this->_configs) return $default;
        $retval = Utils::findConfig('System.' . $configName, $this->_configs, false);
        if ($retval === null) {
            $retval = Utils::findConfig($configName, $this->_configs);
        }
        // if ($retval !== null) {
        $this->_configCache [$configName] = $retval;
        // }
        return $retval === null ? $default : $retval;
    }

    public function getConfigs($configName = null, $default = null)
    {
        if ($configName == null) {
            return $this->_configs;
        }
        //$r = array ();
        if (isset ($this->_configs [$configName])) {
            return $this->_configs [$configName];
        }
        //$nprefix = $configName ? $configName . "." : $configName;
        $r = Utils::findConfig($configName, $this->_configs);
        if ($r === null) {
            return $default;
        }
        return $r;
    }

    private function findConfigFile($f)
    {

        if (is_file($f)) {
            return $f;
        }
        $retval = null;
        if (is_file($f = Utils::changeFileExt($f, 'php'))) {
            $retval = $f;
        } elseif (is_file($f = Utils::changeFileExt($f, 'ini'))) {
            $retval = $f;
        } elseif (is_file($f = Utils::changeFileExt($f, 'config'))) {
            $retval = $f;
        } elseif (is_file($f = Utils::changeFileExt($f, 'xml'))) {
            $retval = $f;
        } elseif (is_file($f = Utils::changeFileExt($f, 'properties'))) {
            $retval = $f;
        }
        return $retval;
    }

    /**
     * @param $ext
     * @return \System\Configurations\Parsers\IConfigurationParser
     * @throws \System\Exceptions\SystemException
     */
    public function getParser($ext)
    {
        if (isset ($this->_parser [$ext])) {
            return $this->_parser [$ext];
        }
        $parser = null;
        switch (strtolower($ext)) {
            case 'manifest' :
            case 'assets' :
            case 'gaf' :
                $ext = 'xml';
                break;
            case 'properties' :
                $ext = 'ini';
                break;
        }
        $c = '\\System\\Configurations\\Parsers\\' . strtoupper($ext) . 'Parser';
        if (class_exists($c, true)) {
            $this->_parser [$ext] = new $c ();
        } else {
            throw new SystemException ("Unhandled configuration $ext");
        }
        return $this->_parser [$ext];
    }

    public function loadFile($f)
    {
        $f = $this->findConfigFile($f);
        if (!$f) {
            return false;
        }
        $this->_configFile = $f;
        if ($this->getCached($f)) return true;

        $ext = Utils::getFileExt($f, false);
        $parser = $this->getParser($ext);
        $configs = null;
        if ($parser) {
            $configs = $parser->parseFile($f);
        }
        if ($configs) {
            $this->Merge($configs);
        }

        return true;
    }

    /**
     * @param bool $return
     * @param bool $handle
     * @return array
     */
    public function Render($return = false, &$handle = false)
    {
        $arr = array();
        foreach ($this->_configs as $k => $v) {
            if ($k === 'System') {
                foreach ($v as $kk => $vv) {
                    $arr [$kk] = $vv;
                }
            } else {
                $arr [$k] = $v;
            }
        }
        return $arr;
    }

    private function getCacheFile()
    {
        return \CGAF::getInternalStorage('.cache/configurations/', false, true) . md5($this->_configFile);
    }

    private function getCached()
    {
        $fname = $this->getCacheFile();
        if (file_exists($fname)) {
            //unlink($fname);
            $mtime = filemtime($this->_configFile);
            $configs = unserialize(file_get_contents($fname));
            //ppd($configs);
            if ($mtime === $configs['mtime']) {
                $this->_dirty = false;
                $this->_configs = $configs['configs'];
                $this->_configCache = $configs['cached'];
                return true;
            } else {
                @unlink($fname);
            }
        }
        return false;
    }
}

/**
 * Class Configurationx
 * @package System\Configurations
 * @deprecated
 */
final class Configurationx
{
    private static $_initialized;
    private static $_instance;

    public static function Init($configs)
    {
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
        self::$_instance = new Configuration ($configs);
        self::$_initialized = true;
        unset ($_configs);
    }

    /**
     * @static
     * @return IConfiguration
     */
    public static function getInstance()
    {
        if (self::$_instance == null) {
            self::Init(null);
        }
        return self::$_instance;
    }

    public static function Merge($_configs)
    {
        return self::getInstance()->Merge($_configs);
    }

    public static function getConfig($configName, $default = null)
    {
        return self::getInstance()->getConfig($configName, $default);
    }

    public static function getConfigs($prefix = null)
    {
        return self::getInstance()->getConfigs($prefix);
    }

    public static function setConfig($configName, $configValue)
    {
        return self::getInstance()->setConfig($configName, $configValue);
    }
}

?>

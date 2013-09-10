<?php
namespace System\Configurations\Parsers;

use Logger;
use System\Configurations\IConfiguration;
use Utils;

class PHPParser implements IConfigurationParser
{
    const NL = "\n";

    function parseFile($f)
    {
        if (!is_file($f)) {
            return null;
        }
        $_configs = include($f);
        return $_configs;
    }

    function parseString($s)
    {
    }

    private function toConfigItem($config, $prev = null, $level = 1, $showComment = false)
    {
        $retval = '';
        if (is_array($config) || is_object($config)) {
            $retval .= 'array(';
            $cnt = 0;
            foreach ($config as $k => $v) {
                $cmt = (is_array($v) || is_object($v)) ? '' : ($showComment ? '//' . sprintf(__('config.' . $prev . '.' . $k, 'Namespace %s'), $prev . '.' . $k) : '');
                $x = is_numeric($k) ? '' : '\'' . $k . '\'=>';
                $retval .= self::NL . str_repeat('	', $level + 1) . $x . $this->toConfigItem($v, $prev . '.' . $k, $level + 1, $showComment);
                if ($cnt < count($config) - 1) {
                    $retval .= ',';
                }
                $retval .= ' ' . $cmt;
                $cnt++;
            }
            $retval .= self::NL . str_repeat('	', $level) . ')';
        } else {
            $t = gettype($config);
            switch ($t) {
                case 'integer':
                    $retval .= $config;
                    break;
                case 'boolean':
                    $retval .= $config ? 'true' : 'false';
                    break;
                case 'string':
                    $retval .= '\'' . $config . '\'';
                    break;
                default:
                    $retval .= $config == null ? 'null' : $config;
                    Logger::info(__CLASS__ . '::' . __FUNCTION__ . 'unknown type ' . $t);
            }
        };
        return ' ' . $retval;
    }

    function toPHPConfig($configs = null, $level = 1, $showComment = false)
    {
        $configs = $configs ? $configs : (isset($this->_configs) ? $this->_configs : array());
        $retval = '<?php ';
        $retval .= 'if (! defined("CGAF"))	die("Restricted Access");';
        $retval .= PHP_EOL;
        $retval .= 'return array(' . PHP_EOL;
        if ($configs) {
            foreach ($configs as $k => $v) {
                $retval .= str_repeat('	', $level) . '\'' . $k . '\'=>' . $this->toConfigItem($v, $k, $level + 1, $showComment) . ',' . PHP_EOL;
            }
        }
        $retval .= ');' . PHP_EOL;
        $retval .= '?>';
        return $retval;
    }

    public function save($fileName, IConfiguration $configs, $settings = null)
    {
        $configs = $this->toPHPConfig($settings ? $settings : $configs->getConfigs());
        file_put_contents($fileName, $configs);
        return true;
    }
}

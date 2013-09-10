<?php
namespace System\Web\JS;

use CGAF;
use System\Exceptions\SystemException;
use System\Web\JS\Packer\IScriptPacker;
use System\Web\Utils\HTMLUtils;
use Utils;

class JSUtils
{
    protected $_mini;
    private static $_instance;

    public static function phptojsString($str)
    {
        $replacer = array(
            "\t",
            "\r\n");
        return Utils::ReplaceString($str, $replacer, "");
    }

    public static function addSlash($str)
    {
        $pattern = array(
            "/\\\\/",
            "/\n/",
            "/\r/",
            "/\"/",
            "/\'/",
            "/&/",
            "/</",
            "/>/");
        $replace = array(
            "\\\\\\\\",
            "\\n",
            "\\r",
            "\\\"",
            "\\'",
            "\\x26",
            "\\x3C",
            "\\x3E");
        return preg_replace($pattern, $replace, $str);
    }

    protected static function getInstance()
    {
        if (!self::$_instance) {
            $class = CGAF::getConfig("js.MinifierClass", "JSMin");
            $class = '\\System\\Web\\JS\\Packer\\' . $class;
            if (!class_exists($class, true)) {
                throw new SystemException('unable to load class ' . $class);
            }
            $c = new $class();
            if (!$c instanceof IScriptPacker) {
                throw new SystemException('Invalid Instance of class ' . $class);
            }
            self::$_instance = $c;
        }
        return self::$_instance;
    }

    public static function PackFile($jsfile, $dest = null, $group = 'js')
    {
        $cm = \AppManager::getInstance()->getCacheManager();
        if (is_array($jsfile)) {
            $js = '';
            $cm->remove($dest, $group, true);
            foreach ($jsfile as $j) {
                $packed = self::Pack(file_get_contents($j));
                $cm->put($dest, $packed, $group, true);
            }
            return Utils::LocalToLive($cm->get($dest, $group));
        }
        if (!is_file($jsfile)) {
            return $jsfile;
        }
        $dest = $dest === null ? basename(Utils::changeFileExt($jsfile, 'min.js')) : $dest;
        $ori = $cm->get($dest, 'js');
        if (!$ori) {
            $packed = self::Pack(file_get_contents($jsfile));
            $ori = $cm->put($dest, $packed, 'js');
        }
        return Utils::LocalToLive($ori, 'js');
    }

    public static function Pack($script)
    {
        $packer = self::getInstance();
        $packer->setScript($script);
        return $packer->pack();
    }

    public static function packAndSave($script, $dest, $append = false)
    {
        $packed = self::Pack($script);
        if (!$append && is_file($dest)) {
            unlink($dest);
        }
        file_put_contents($dest, $packed);
        return $packed;
    }

    public static function renderJSFile($js, $target = null)
    {
        $app = \AppManager::getInstance();
        $jsname = $app->getCacheManager()->getCachePath('js') . $target;
        if (!CGAF_DEBUG && !is_readable($jsname)) {
        }
        $retval = '';
        foreach ($js as $f) {
            $fname = $app->getAsset(Utils::changeFileExt($f, "min.js"), "js");
            if (!$fname) {
                $fname = $app->getAsset($f, "js");
            }
            $fname = $app->getLiveData($fname);
            if ($fname) {
                $retval .= self::renderJSTag($fname);
            } else {
                throw new SystemException('error.filenotfound', $f);
            }
        }
        return $retval;
    }

    public static function renderJSTag($f, $file = true, $attr = null)
    {
        if ($file) {
            return '<script type="text/javascript" src="' . $f . '" ' . HTMLUtils::renderAttr($attr) . '></script>';
        } else {

            if (is_array($f)) {
                $f = implode(''.(CGAF_DEBUG ? PHP_EOL :null), $f);
            }
            return $f ? '<script type="text/javascript">' . $f . '</script>' : '';
        }
    }
}

?>
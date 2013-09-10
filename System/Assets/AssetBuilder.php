<?php
/**
 * Enter description here ...
 *
 */
namespace System\Assets;

use AppManager;
use CDate;
use CGAF;
use Strings;
use System\Applications\IApplication;
use System\Configurations\Configuration;
use System\Exceptions\SystemException;
use Utils;

class AssetManifest extends Configuration
{
    private $_manifestFile = null;

    function __construct($fname)
    {
        parent::__construct(null, false);
        $this->_manifestFile = $fname;
        $this->loadFile($fname);
    }

    function Save($fileName = null, $settings = NULL)
    {
        $fileName = $fileName ? $fileName : $this->_manifestFile;
        return parent::Save($fileName);
    }
}

class AssetProjectFile
{
    private $_source;
    private $_vars = array();
    /**
     * @var \System\Applications\IApplication
     */
    private $_appOwner;
    private $_targetPath;
    //private $_parsers;
    /**
     * @var \System\Configurations\IConfiguration
     */
    private $_config;
    //private $_parsed;
    private $_files;
    private $_manifestFile;
    /**
     * @var AssetManifest
     */
    private $_manifest;

    function __construct($src, $appOwner)
    {
        $this->_source = $src;
        $this->_appOwner = $appOwner ? $appOwner : AppManager::getInstance();
        $this->_vars = array('$tmppath' => CGAF::getTempPath(),
            '$assetpath' => $this->_appOwner->getLivePath(),
            '$cachepath' => $this->_appOwner->getCachePath(),
            '$globalAssetPath' => SITE_PATH . 'assets/',
            '$themes' => $this->_appOwner->getConfig('app.themes', 'ui-lightness'));
        $this->reset();
    }

    private function fixPath($igs)
    {
        $retval = array();
        foreach ($igs as $v) {
            if (stripos($v, '@') !== false) {
                $fi = $this->_config->getConfig('assets.' . str_replace('@', '', $v), array());
                if ($fi) {
                    $retval = array_merge($fi['file'], $retval);
                }
            } else {
                $retval[] = $v;
            }
        }
        return $retval;
    }

    private function findFile($f, $srcPath, $ign = array())
    {
        if (is_string($f) && strpos($f, '://') !== false) {
            return $f;
        }

        $file = Utils::ToDirectory(Strings::replace($f, $this->_vars));

        $ap = array_merge(array(Utils::ToDirectory($srcPath)), $this->_appOwner->getAssetPath(''));

        $srcPath = Utils::ToDirectory($srcPath);

        if (is_array($file)) {
            $r = array();
            $ignore = array();
            if (isset($file['@excludes'])) {
                $ignore = $this->fixPath(explode(',', $file['@excludes']));
            } elseif (isset($file['@includes'])) {
                $f = $this->fixPath(explode(',', $file['@includes']));

            }
            foreach ($f as $k => $v) {
                if (is_numeric($k)) {
                    $ff = $this->findFile($v, $srcPath, $ignore);
                    if ($ff) {
                        if (is_array($ff)) {
                            $r = array_merge($r, $ff);
                        } else {

                            $r[] = $ff;
                        }
                    }
                }
            }

            return $r;
        }

        $file = trim($file);

        if (Strings::contains($file, '@')) {

            $file = Strings::FromPos($file, '@');
            $fs = $this->_config->getConfig('assets.' . $file);
            if (!$fs) {
                throw new SystemException('Unknown Variable ' . $file);
            }
            $fs = $fs['file'];
            $retval = array();
            foreach ($fs as $k => $v) {
                $r = $this->findFile($v, $srcPath);
                if (is_array($r)) {
                    $retval = array_merge($r, $retval);
                } else {
                    $retval[] = $r;
                }
            }

            return $retval;
        }
        if (stripos($file, '*') !== false) {
            $x = Strings::replace(Strings::FromLastPos($file, DS), array('*' => '', '.' => '\\.'));
            $m = '/' . $x . '$/D';
            $file = Utils::ToDirectory($srcPath . DS . $file);

            if (!is_dir(dirname($file))) {

                return null;
            }

            $file = Utils::getDirFiles(dirname($file), dirname($file) . DS, false, $m);
        } elseif (is_file($file)) {
            return $file;
        }

        $ori = $file;
        if (!is_array($file)) {
            $file = array($file);
        }
        $retval = array();
        foreach ($file as $rf) {
            if (is_file($rf) && !in_array(basename($rf), $ign)) {
                $retval[] = $rf;
            }
        }
        $found = false;

        foreach ($file as $f2) {
            foreach ($ap as $apf) {
                $ff = $apf . $f2;
                if (is_file($ff) && !in_array(basename($ff), $ign)) {
                    $retval[] = $ff;
                    break;
                }
            }
        }

        return $retval;
    }

    private function _loadManifestFile($force = false)
    {
        if ($force || $this->_manifest === null) {
            $this->_targetPath = Utils::ToDirectory(Strings::replace($this->getConfig('assets.configs.TargetPath'), $this->_vars));
            $manifestpath = $this->_appOwner->getInternalStoragePath() . 'asset-manifest' . DS;
            $this->_manifestFile = $manifestpath . md5($this->_source) . '.manifest';
            Utils::removeFile($this->_manifestFile);
            if (!is_file($this->_manifestFile)) {
                $this->_manifest = $this->_resetManifest();
            } else {
                $this->_manifest = new AssetManifest($this->_manifestFile);
            }
            if ($this->_manifest->getConfig('Manifest.DateCreated') !== \CDate::getMFileTime($this->_source)) {
                return $this->_resetManifest();
            }
        }
        return $this->_manifest;
    }

    private function getConfig($configName, $def = null)
    {
        if (!$this->_config) {
            $this->_config = new Configuration(null, false);
            $this->_config->loadFile($this->_source);
        }
        return $this->_config->getConfig($configName, $def);
    }

    private function _resetManifest()
    {
        $this->_files = array();

        $files = $this->getConfig('assets.files', array());
        $orit = $this->getConfig("assets.configs.Target");
        $join = isset($files["@join"]) ? \Convert::toBool($files["@join"]) === true : CGAF_DEBUG === false;
        $files["@join"] = $join;
        $sp = Utils::ToDirectory(dirname($this->_source) . DS . $this->getConfig("assets.configs.SourcePath", ''));
        $srcPath = realpath($sp);
        if (!$srcPath) {
            if (CGAF_DEBUG) {
                throw new SystemException('Source path [' . $sp . '] not found on file ' . $this->_source);
            } else {
                ppd($this->_source);
            }
        }

        $srcPath = $srcPath . DS;
        if ($files && isset($files['file'])) {
            $files = $files['file'];

            if (is_string($files)) {
                $files = array($files);
            }

            foreach ($files as $file) {
                $f = null;
                $target = '';
                $j = $join;
                if (!$file)
                    continue;
                if (is_array($file)) {
                    if (isset($file['@target'])) {
                        $target = $file["@target"];
                    }
                    if (isset($file['@join'])) {
                        $j = \Convert::toBool($file["@join"]);
                    }
                }

                $f = $this->findFile($file, $srcPath);
                if (!$f) {
                    $f = $this->findFile($file, $this->getConfig("assets.configs.SourcePath", ''));
                }
                if ($f) {
                    if (is_array($f)) {
                        foreach ($f as $ff) {
                            $fx = trim(Utils::changeFileExt($orit, Utils::getFileExt($ff, false)));
                            $target = $target ? $target : $fx;
                            //$parser = self::getParser(Utils::getFileExt($file, false));
                            $this->_files[$target][] = realpath($ff);
                        }
                    } else {
                        $fx = trim(Utils::changeFileExt($orit, Utils::getFileExt($f, false)));
                        $target = $target ? $target : $fx;
                        $this->_files[$target][] = realpath($f);
                    }
                } else {
                    throw new SystemException($file . '@' . $this->_source . '[relative path :' . $srcPath . ']');
                    //\Logger::info($this->_source . ':File Not Found' . $file);
                }
            }
        }
        return $this->_writeManifest(false);
    }

    private function reset()
    {
        return $this->_loadManifestFile();

    }

    private function _writeManifest($sdate = true)
    {
        $manifest = new AssetManifest($this->_manifestFile);
        $manifest->clear();
        $manifest->setConfig("Manifest.Source", $this->_source);
        $manifest->setConfig("Manifest.TargetPath", $this->_targetPath);
        $manifest->setConfig("Manifest.DateCreated", \CDate::getMFileTime($this->_source));
        foreach ($this->_files as $r => $file) {
            $res = array();
            foreach ($file as $f) {
                $item = array('file' => $f, 'date' => $sdate ? CDate::getMFileTime($f) : null);
                $res[] = $item;
            }
            $manifest->setConfig('Resources.' . str_replace('.', '#', $r), $res);
        }
        $manifest->Save();
        $this->_manifest = $manifest;
        return $manifest;
    }

    private function _getParse($file)
    {
        $retval = array();
        foreach ($file as $value) {
            $retval[] = $value['file'];
        }
        return $retval;
    }

    function parse()
    {
        $manifest = $this->_loadManifestFile();
        $resources = $manifest->getConfigs('Resources');
        $targetPath = $manifest->getConfig('Manifest.TargetPath');
        $toparse = array();
        $retval = array();
        foreach ($resources as $t => $value) {
            $t = str_replace('#', '.', $t);
            $tf = Utils::ToDirectory($targetPath . DS . $t);
            $retval[] = $tf;
            if (!is_file($tf)) {
                $toparse[$tf] = $this->_getParse($value);
            } else {
                $changed = false;
                foreach ($value as $v) {
                    if ($v['date'] !== \CDate::getMFileTime($v['file'])) {
                        $changed = true;
                        break;
                    }
                }
                if ($changed) {
                    $toparse[$tf] = $this->_getParse($value);
                }
            }
        }

        if ($toparse) {
            $retval = array();
            foreach ($toparse as $dest => $file) {
                $ext = Utils::getFileExt($dest, false);
                Utils::removeFile($dest);
                $parser = AssetBuilder::getparser($ext);
                if (!$parser) {
                    throw new SystemException("Unable to get project parser " . $ext);
                }
                Utils::makeDir(dirname($dest));
                $fdest = $parser->parseFile($file, $dest, true);
                $retval = array_merge($retval, $fdest);
            }
            $this->_writeManifest();
        }

        $tmp = $retval;
        $retval = array();
        foreach ($tmp as $v) {
            if (is_array($v)) {
                ppd($v);
            }
            if (is_file($v) && !in_array($v, $retval)) {
                $retval[] = $v;
            }
        }
        return $retval;
    }
}

abstract class AssetBuilder
{

    private static $parsers = array();
    //private static $_config;
    private static $_files = array();

    /**
     *
     * @param string $ext
     * @return \System\Assets\Parsers\AbstractProjectParser
     */

    public static function getparser($ext)
    {
        if (!isset(self::$parsers[$ext])) {
            $rets = using('System.Assets.Parsers.' . $ext);
            $class = '\\System\\Assets\\Parsers\\' . $ext . 'ProjectParser';
            if (class_exists($class, false)) {
                $parser = new $class();
                self::$parsers[$ext] = $parser;
            }
        }
        return self::$parsers[$ext];
    }

    private function addFile($f)
    {
        $this->_files[] = $f;
    }

    private static function getCachedMTime($id, $t)
    {
        static $_cached, $fcache;
        if (!$fcache) {
            $cm = CGAF::getCacheManager()->getCachePath();
            $fcache = Utils::ToDirectory($cm . 'mtime.cache');
        }
        if (!$_cached) {
            if (is_file($fcache)) {
                $_cached = unserialize(file_get_contents($fcache));
            } else {
                $_cached = array();
            }
        }
        $retval = null;
        if (isset($_cached[$id])) {
            $retval = $_cached[$id];
        }

        $_cached[$id] = $t;
        file_put_contents($fcache, serialize($_cached));
        return $retval;
    }

    private static function _isFileChanged($file)
    {
        if (is_array($file)) {
            $changed = false;
            foreach ($file as $f) {
                if (self::_isFileChanged($f)) {
                    $changed = true;
                }
            }
            return $changed;
        }
        $id = hash('crc32b', $file);
        $t = CDate::getMFileTime($file);
        $t2 = self::getCachedMTime($id, $t);
        if ($t === $t2) {
            return false;
        }
        //pp ( $file . '[' . $t . '<>' . $t2 . ']' );
        return true;
    }

    private static function getManifest($f, IApplication $appOwner)
    {
        $manifest = new Configuration(null, false);
        $manifest->loadFile($f);
        $v = $manifest->getConfig('manifest.@version') !== null ? $manifest->getConfig('manifest.@version') : time();
        $files = $manifest->getConfig('manifest.file');
        if (!$files) {
            $files = array();
        } elseif (!is_array($files)) {
            $files = array($files);
        }

        $retval = array();
        foreach ($files as $file) {
            $live = $appOwner->getLiveData($file);
            if ($live) {
                $retval[] = \URLHelper::addParam($live, array('v' => $v));
            }
        }
        return $retval;
    }

    public static function build($src, $appOwner = null)
    {
        if (!is_file($src)) {
            return null;
        }
        $appOwner = $appOwner ? $appOwner : AppManager::getInstance();
        $file = new AssetProjectFile($src, $appOwner);
        return $file->parse();
    }

    public static function buildFiles($files, $dest)
    {
        $ext = Utils::getFileExt($dest, false);
        $dest = Utils::ToDirectory(Utils::changeFileExt($dest, 'min.' . $ext));
        Utils::makeDir(dirname($dest));

        $retval = array();
        if (is_file($dest)) {
            return array($dest);
        }
        if (CGAF_DEBUG) {
            Utils::removeFile($dest);
        }

        foreach ($files as $f) {
            $fdest = $dest;
            $fext = Utils::getFileExt($f, false);
            if ($fext !== $ext) {
                $fdest = dirname($dest) . DS . Utils::changeFileExt(basename($f), 'min.' . $fext);
            }
            $parser = self::getparser($fext);
            $fdest = $parser->parseFile($f, $fdest, true);
            if (is_array($fdest)) {
                foreach ($fdest as $fd) {
                    if (!in_array($fd, $retval)) {
                        $retval[] = $fd;
                    }
                }
            } else {
                if (!in_array($fdest, $retval)) {
                    $retval[] = $fdest;
                }
            }
        }

        return $retval;
    }

}

?>
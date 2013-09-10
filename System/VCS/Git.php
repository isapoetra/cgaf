<?php
/**
 * Git.php
 * User: e1
 * Date: 3/16/12
 * Time: 7:50 PM
 */
namespace System\VCS;

use Git2\Reference;
use Git2\Repository;

interface IGitCommandWrapper
{
    function getBranch();

    function checkout($branch);

    function isInBranch($branch);

    function pull();
}

class GitRepoInfo
{
    public $ref;

    function isInBranch($branch)
    {
        return in_array($branch, $this->ref);
    }
}

class GitDirectWrapper implements IGitCommandWrapper
{
    private $_repo;
    private $_gitPath;
    /**
     * @var GitRepoInfo
     */
    private $_info;

    function __construct($repo)
    {
        $this->_repo = \CGAF::toDirectory($repo . DS);
        $this->_initialize();
    }

    function getTag()
    {

    }

    function getBranch()
    {
        if (!$this->_info) {
            return '';
        }
        return implode(',', $this->_info->ref);
    }

    private function _debug($msg)
    {
        if (CGAF_DEBUG) {
            \Response::getInstance()->WriteDebug($msg);
        }
    }

    function isInBranch($branch)
    {
        if (!$this->_info) return false;
        return $this->_info->isInBranch($branch);
    }

    function checkout($branch)
    {
        $ret = $this->gitExec('checkout ' . $branch);

    }

    function pull()
    {
        $ret = $this->gitExec('pull');
        \Response::write($ret);
    }

    private function gitExec($command)
    {
        $command = 'GIT_DIR=' . escapeshellarg($this->_gitPath) . ' git ' . $command;
        $this->_debug($command);
        $out = array();
        $r = exec($command, $out);
        return $out;
    }

    private function _initialize()
    {
        $f = $this->_repo . ".git";
        if (is_dir($f))
            $this->_gitPath = $this->_repo;
        elseif (is_file($f)) {
            //from sub module ?
            $this->_gitPath = trim(substr(file_get_contents($f), 8));
        }
        if ($this->_gitPath) {
            $ref = $this->gitExec('log --pretty=format:\'%ad %h %d\' --abbrev-commit --date=short -1');
            $ref = explode(' ', trim($ref[0], ' () '));
            $this->_info = new GitRepoInfo();
            $this->_info->dateModified = array_shift($ref);
            $this->_info->hash = array_shift($ref);
            array_shift($ref);
            $ref = explode(', ', trim(implode(' ', $ref), '() '));
            $this->_info->ref = $ref;
        }

    }

}

class GitRepo implements IGitCommandWrapper
{
    private $_path;
    private $_reference;
    /**
     * @var IGitCommandWrapper
     */
    private $_wrapper;

    function __construct($path, IGitCommandWrapper $wrapper = null)
    {
        if ($wrapper === null) {
            $wrapper = new GitDirectWrapper($path);
        }
        $this->_wrapper = $wrapper;
    }

    public function getBranch()
    {
        return $this->_wrapper->getBranch();
    }

    public function isInBranch($branch)
    {
        return $this->_wrapper->isInBranch($branch);
    }

    public function checkout($branch)
    {
        return $this->_wrapper->checkout($branch);
    }

    public function pull()
    {
        return $this->_wrapper->pull();
    }
}

abstract class Git
{
    private static $_initialized = false;

    static function initialize()
    {
        if (self::$_initialized) {
            return true;
        }
        /*if (!\System::loadExtenstion('git2')) {
            throw new SystemException('git2 extension not loaded. please refer to https://github.com/libgit2/php-git');
        }  */
    }

    /**
     * @static
     * @param $path
     * @return GitRepo
     */
    public static function getRepo($path)
    {
        $repo = new GitRepo($path);
        return $repo;
    }

}

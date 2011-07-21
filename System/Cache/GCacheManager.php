<?php

class GCacheManager {
	//minute
	private $_cacheTimeOut;
	private $_cachePath;
	private $_mcache = array();
	function __construct() {
		$this->_cacheTimeOut = (CGAF_DEBUG ? 5 : 60);
		$this->setCachePath(CGAF::getTempPath() . DS . "cache" . DS);
	}
	function setCacheTimeOut($value) {
		$this->_cacheTimeOut = $value;
	}
	function setCachePath($path) {
		$this->_cachePath = Utils::toDirectory($path);
		Utils::makeDir($this->_cachePath);
	}
	function getId($o) {
		return md5($o);
	}
	function remove($id, $group, $force = true,$ext=null) {
		if (isset($this->_mcache[$id])) {
			unset($this->_mcache[$id]);
		}
		$fname = $this->getFileName($id, $group,$ext);

		if (is_readable($fname)) {
			if ($force || ! $this->isCacheValid($fname)) {
				@unlink($fname);
			}
			return true;
		}
		return false;
	}
	function getCachePath($group=null, $sessionBased = false) {
		$retval = Utils::ToDirectory($this->_cachePath . DS. $group . DS.($sessionBased ? session_id()  : ""). DS);

		Utils::makeDir($retval, 0700);
		return $retval;
	}
	function getFileName($id, $group, $ext = null) {
		return Utils::toDirectory($this->getCachePath($group) . Utils::getFileName($id,true) . $ext);
	}
	function putString($s, $id, $group='common', $ext=null,$append = false) {

		$fname = $this->getFileName($id, $group, $ext);

		Utils::makeDir(dirname($fname));
		if ($append) {
			file_put_contents($fname, $s,FILE_APPEND);
		}else{
			file_put_contents($fname, $s);
		}
		return $fname;
	}
	function isCacheValid($fname,$timeout =null) {
		if (! is_readable($fname)) {
			return false;
		}
		if ((int)$timeout ===0) {
			return TRUE;
		}
		$timeout = $timeout ==null ? $this->_cacheTimeOut : $timeout;
		$Diff =(time() - filemtime($fname))/60/60;

		return ($Diff < $timeout);
	}
	function getContent($id, $prefix, $suffix = null,$timeout=NULL) {
		$fname = $this->get($id, $prefix, $suffix);
		if (is_readable($fname)) {
			return file_get_contents($fname);
		}
		return null;
	}
	function get($id, $prefix, $suffix = null,$timeout=NULL) {
		if (CGAF::getConfig("disableCache",false)) {
			return null;
		}
		
		if (isset($this->_mcache[$id])) {
			return $this->_mcache[$id];
		}
		$fname = $this->getFileName($id, $prefix, $suffix);
		if (is_file($fname)) {

			if (! $this->isCacheValid($fname,$timeout) && is_file($fname)) {
				@unlink($fname);
				return null;
			}
			$this->_mcache[$id] = $fname;
			return $fname;
		}
		return null;
	}
	
	function put($id, $o, $group,$add=false,$ext=null) {
		if (isset($this->_mcache[$id])) {
				unset($this->_mcache[$id]);
		}
		if (!CGAF::getConfig("disableCache",false)) {
			$fname = $this->getFileName($id, $group,$ext);
			if (!is_string($o)) {
				$o =  serialize($o);
			}
			file_put_contents($fname, $o,$add ? FILE_APPEND : null);
			return $fname;
		}
		return null;
	}
	function putFile($fname, $id, $callback = null, $group = "misc") {
		$retval = $this->get($id, $group, null);

		if (! $retval) {
			//pp($fname);
			$content = "";
			if (is_array($fname)) {

				foreach ( $fname as $v ) {
					$content .= file_get_contents($v);
				}
			} else {
				if (is_file($fname)) {
					$content = file_get_contents($fname);
				}
			}

			if ($callback && $content) {
				$content = call_user_func_array($callback, array(
						"rpath"=>$fname,
						"content"=>$content,
						"id"=>$id,
						"group"=>$group));
			}
			if (! $content) {
				return null;
			}
			$retval = $this->putString($content, $id, $group, null);
		}
		return $retval;
	}
}
?>
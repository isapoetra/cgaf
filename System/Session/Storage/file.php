<?php
namespace System\Session\Storage;
use System\Session\Session;

use System\Session\SessionBase as SessionBase, \CGAF as CGAF, \Utils as Utils;

function serialize_fix_callback($match) {
	return 's:' . strlen($match[2]);
}

class File extends SessionBase {
	private $_savePath;
	private $_sessionName = "CGAF";

	function __construct() {
		parent::__construct();
		$this->_savePath = Utils::ToDirectory($this->getConfig("save_path", CGAF::getInternalStorage('session', false)));
		if (!is_readable($this->_savePath)) {
			Utils::makeDir($this->_savePath, 0770);
		}
		Utils::securePath($this->_savePath, '*');
	}

	function open($savePath, $sessName) {
		$this->_savePath = $savePath;
		$this->_sessionName = $sessName;
		return true;
	}

	private function unserialize($data) {
		$serialized = preg_replace_callback('!(?<=^|;)s:(\d+)(?=:"(.*?)";(?:}|a:|s:|b:|i:|o:|N;))!s', 'serialize_fix_callback', $data);
		return unserialize($serialized);
	}

	private function getFileName($sesid) {
		return Utils::ToDirectory($this->_savePath . DS . "sess_$sesid");
	}

	function read($sessID) {
		$sess_file = $this->getFileName($sessID);
		if (is_readable($sess_file)) {
			return base64_decode((string)file_get_contents($sess_file));
		}
		return ("");
	}

	function write($sessID, $sessData) {
		parent::write($sessID, $sessData);
		$sess_file = $this->getFileName($sessID);
		$fp = @fopen($sess_file, "w+");
		if ($fp) {
			$return = fwrite($fp, base64_encode($sessData));
			fclose($fp);
			return $return;
		} else {
			return (false);
		}
	}

	function destroy($sessID = null) {
		if ($this->getSessionState() === Session::STATE_DESTROYED) {
			return true;
		}
		if ($sessID == null)
			$sessID = session_id();
		$sess_file = $this->getFileName($sessID);
		if (is_readable($sess_file)) {
			@unlink($sess_file);
		}
		if ($sessID && $sessID === $this->getId()) {
			return parent::destroy($sessID);
		}
		return true;
	}

	function gc($sessMaxLifeTime) {
		parent::gc($sessMaxLifeTime);
		foreach (glob($this->_savePath . "/sess_*") as $filename) {
			if (filemtime($filename) + $sessMaxLifeTime < time()) {
				@unlink($filename);
			}
		}
		return true;
	}


}

?>
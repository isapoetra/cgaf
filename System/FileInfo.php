<?php
final class FileInfo extends Object {
	private $_file;
	private $_mime = null;
	function __construct($file) {
		parent::__construct();
		$this->_file = $file;
	}
	public static function getMimeFromExt($fileext) {
		static $mime;
		if (!isset($mime[$fileext])) {
			$regex = "/^([\w\+\-\.\/]+)\s+(\w+\s)*($fileext\s)/i";
			$lines = file(CGAF::getInternalStorage(null, false) . DS
					. "mime.types");
			foreach ($lines as $line) {
				if (substr($line, 0, 1) == '#')
					continue;
				// skip comments
				$line = rtrim($line) . " ";
				if (!preg_match($regex, $line, $matches))
					continue;
				// no match to the extension
				$mime[$fileext] = $matches[1];
			}
		}
		return isset($mime[$fileext]) ? $mime[$fileext] : null;
	}
	public static function getFileMimeType($file) {
		$fileext = substr(strrchr($file, '.'), 1);
		return self::getMimeFromExt($fileext);
	}
	function getMime() {
		if (!$this->_mime) {
			$this->_mime = self::getFileMimeType($this->_file);
		}
		return $this->_mime;
	}
}

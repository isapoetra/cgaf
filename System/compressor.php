<?php

abstract class Compressor {
	private static $_instance;
	static function compressString($string,$format,$options=array()) {
		$compressor = self::getCompressor($format);
		$compressor->reset();
		$compressor->setConfigs($options);
		
		return $compressor->compressString($string);
	}
	static function compressFile($fileName,$options=array()) {
		if (!is_file($fileName)) {
			return '';
		}
		$compressor = self::getCompressor(Utils::getFileExt($fileName,false));
		$compressor->reset();
		$compressor->setCurrentPath(dirname($fileName));
		$compressor->setConfigs($options);		
		return $compressor->compressString(file_get_contents($fileName));
	}
	static function getCompressor($format) {
		$format = strtoupper($format);
		if (!isset(self::$_instance[$format])) {
			$c = "\\System\\Compressor\\".$format.'Compressor';
			self::$_instance[$format] = new $c();
		}
		return self::$_instance[$format];
	}
}

?>
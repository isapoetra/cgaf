<?php
namespace System\Cache;
use \CGAF;

class CacheFactory {
	private static $_instance;
	static function getInstance() {
		if (self::$_instance == null) {
			$class = '\\System\\Cache\\Engine\\' . CGAF::getConfig("cache.engine", "Base");
			self::$_instance = new $class();
		}
		return self::$_instance;
	}
	static function get($id, $suffix = null) {
		return self::getInstance()->get($id, $suffix);
	}
	static function putString($s, $id, $ext = null) {
		return self::getInstance()->putString($s, $id, $ext);
	}
	static function getId($o) {
		return self::getInstance()->getId($o);
	}
	static function isCacheValid($fname) {
		$id = self::getId($fname, Utils::getFileExt($fname));
		return self::getInstance()->isCacheValid($id);
	}
	static function putFile($fname, $callback = null) {
		return self::getInstance()->putFile($fname, $callback);
	}
	static function remove($id, $group) {
		return self::getInstance()->remove($id, $group);
	}
}

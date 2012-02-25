<?php
namespace System\Configurations\Parsers;

use System\JSON\JSON;

class JSONParser implements IConfigurationParser {
	private $_indent =true;
	function parseFile($f) {
		if (is_file($f)) {
			$data = file_get_contents($f);
			return $this->parseString($data);
		}
	}

	function parseString($s) {
		$s = json_decode($s);
		if ($s) {
			return $s;
		}
		ppd(json_last_error());
	}

	function save($fileName, $configs, $settings = null) {

		if (!is_string($configs)) {
			$configs = json_encode($configs->getConfigs());
		}
		if ($this->_indent) {
			$configs = JSON::indent($configs);
		}
		if (!json_last_error()) {
			file_put_contents($fileName, $configs);
			return true;
		}
		return false;
	}
}

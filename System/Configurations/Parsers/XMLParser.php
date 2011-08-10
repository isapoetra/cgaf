<?php
namespace System\Configurations\Parsers;
use \Utils;
class XMLParser implements IConfigurationParser {
	/**
	 * @param unknown_type $f
	 */
	public function parseFile($f) {
		return $this->parseString(file_get_contents($f));
	}

	/**
	 * @param unknown_type $s
	 */
	public function parseString($s) {
		$doc =  new \DOMDocument();
		$doc->loadXML($s);
		return \XMLUtils::toArray($doc);
	}
	public function save($fileName, $configs,$settings=null) {

		$cfgs =  $configs->getConfigs();
		$xml = Utils::toXML($cfgs,$settings);
		try {
			Utils::removeFile($fileName);
		}catch (Exception $e) {
		}
		Utils::makeDir(dirname($fileName));
		file_put_contents($fileName, $xml);
	}

}

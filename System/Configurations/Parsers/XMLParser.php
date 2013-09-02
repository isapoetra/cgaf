<?php
namespace System\Configurations\Parsers;
use System\Configurations\IConfiguration;
use \Utils;
class XMLParser implements IConfigurationParser {
	/**
	 * @param string $f
     * @return array
     */
	public function parseFile($f) {
		return $this->parseString(file_get_contents($f));
	}

	/**
	 * @param string $s
     * @return array
     */
	public function parseString($s) {
		$doc =  new \DOMDocument();
		$doc->loadXML($s);
		return \XMLUtils::toArray($doc);
	}
	public function save($fileName, IConfiguration $configs,$settings=null) {

		$cfgs =  $configs->getConfigs();
		$xml = \Convert::toXML($cfgs,$settings);
		try {
			Utils::removeFile($fileName);
		}catch (\Exception $e) {
		}
		Utils::makeDir(dirname($fileName));
		file_put_contents($fileName, $xml);
	}

}

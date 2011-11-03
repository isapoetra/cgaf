<?php
namespace System\Documents;
abstract class ODF extends \Object {
	const GENERATOR_VERSION = '0.1';
	const GENERATOR_NAME = 'cgaf based on DIO';
	static function open($f, $ext = null) {
		if (is_file($f)) {
			$ext = strtolower($ext ? $ext : \Utils::getFileExt($f, false));
			$c = __NAMESPACE__ . '\\ODF\\';
			switch ($ext) {
			case 'ods':
				$c .= 'SpreadSheet';
				break;
			case 'fods':
				$c .= 'SpreadSheetFlat';
				break;
			default:
			}
			$instance = new $c($f);
		}
		return $instance;
	}
	static function create($content) {
		//TODO Generate folder META-INF
		//TODO Generate folder Configurations2
		//TODO Generate folder thumbnails
		//TODO Generate content.xml based on $content
		//TODO Generate meta.xml based on $content
		//TODO Generate mimetype based on $content
		//TODO Generate settings.xml based on $content
		//TODO Generate styles.xml based on $content
	}
}

<?php
namespace System\Web\UI\Ext;
use \Utils;
use System\JSON\JSON;
class JsonDataStore extends ExtJS {

	function __construct($data, $configs = array()) {
		$fields = array ();
		foreach ( $data [0] as $fieldk => $fieldv ) {
			$fields [] = $fieldk;
		}
		$initconfigs = array ("fields" => $fields, "data" => $data );
		$configs = Utils::arrayMerge ( $initconfigs, $configs );
		$configs = JSON::encodeConfig ( $configs );
		$js = "new Ext.data.JsonStore(" . $configs . ")";
		parent::__construct ( $js );
	}
}
<?php
namespace System\Web\UI\Ext;
use Utils;
class GroupingStore extends CustomComponent {
	
	function __construct($url, $configs, $field) {
		$initconfigs = array (
				"root" => "rows", 
				"totalProperty" => "results", 
				"url" => \URLHelper::addParam ( $url, '__data=json' ), 
				"autoLoad" => array (
						"params" => array (
								"start" => 0, 
								"limit" => 10 
						) 
				), 
				"sortInfo" => array (
						"field" => $field, 
						"direction" => "ASC" 
				), 
				"remoteSort" => true, 
				"groupField" => $field 
		);
		$configs = Utils::arrayMerge ( $initconfigs, $configs );
		parent::__construct ( "GExt.data.GroupingStore", $configs, null, false );
	}
}
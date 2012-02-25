<?php
namespace System\Web\UI\Ext;
class JsonStore extends CustomComponent {
	function __construct($url, $configs = null) {
		$initconfigs = array ("root" => "rows", "totalProperty" => "results", "url" => $url, "autoLoad" => array ("params" => array ("start" => 0, "limit" => 10 ) ), "remoteSort" => true );
		$configs = \Utils::arrayMerge ( $initconfigs, $configs );
		parent::__construct ( "GExt.JsonStore", $configs, null, false );
	}
}
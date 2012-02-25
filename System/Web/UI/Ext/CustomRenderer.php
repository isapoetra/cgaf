<?php
namespace System\Web\UI\Ext;
class CustomRenderer extends ExtJS {

	function __construct($tpl, $params) {
		if (! is_string ( $params )) {
			$params = JSON::encode ( $params, false, array_keys ( $params ) );
			$params = str_replace ( "\"", "", $params );
		}
		$js = "function(value,g,r){
    		G.dump(r);
      	tpl = new Ext.Template('" . $tpl . "');
      	return tpl.applyTemplate($params);}";
		parent::__construct ( $js );
	}
}

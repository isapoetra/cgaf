<?php 
namespace System\Configurations\Parsers;
use \Utils;
class INIParser extends AbstractConfigParser implements IConfigurationParser  {
	function parseFile($f) {
		if (!is_file($f)) {
			return null;
		}
		$retval = array();
		$arrs =  Utils::parseIni($f);
		foreach($arrs as $k=>$v) {
			 if ($k==='Default') {
			 		foreach($v as $kk=>$vv) {
				 		$retval['System'][$kk] =  $vv;
			 		}
			 }else{
			 		$retval[$k]=$v;
			 }
		}
		unset($arrs);
		return $retval;
	}
	function parseString($s) {
	
	
	}
	public function save($fileName, $configs,$settings=null) {
		
	}
}
?>

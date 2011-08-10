<?php
namespace System\Configurations\Parsers;
interface IConfigurationParser {
	function parseFile($f);
	function parseString($s);
	function save($fileName, $configs,$settings=null);
}
?>

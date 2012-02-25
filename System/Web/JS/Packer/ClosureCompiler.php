<?php
namespace System\Web\JS\Packer;
using ('libs.php-closure.php-closure' );
class ClosureCompilerPacker extends PhpClosure implements IScriptPacker {
	private $_instance;
	private $_scripts;
	function __construct() {
		parent::PhpClosure();
	}
	private function getInstance() {
		$c = new PhpClosure ();
		$c->add ( "my-app.js" )
		->add ( "popup.js" )->advancedMode ()->useClosureLibrary ()->cacheDir ( "/tmp/js-cache/" )->write ();
	}
	function setScript($s) {
		$this->_scripts = $s;
	}
	function  pack() {
		ppd($this);
	}
}

?>
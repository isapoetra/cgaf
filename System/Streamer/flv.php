<?php
namespace System\Streamer;
class FLV implements \IStreamer {
	private $_file;
	function __construct($file) {
		$this->_file = $file;
	}
	function stream() {

	}
}
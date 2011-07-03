<?php
defined("CGAF") or die("Restricted Access");

abstract  class TResponse extends Object implements IResponse, IRenderable {
	private $_buffered = true;
	private $_buffer = null;
	private $_buff = array();
	private $_idx = 0;

	function __construct($buffered = true) {
		if (is_array($buffered)) {
			foreach ( $buffered as $k => $v ) {
				$this->$k = $v;
			}
		} else {
			$this->_buffered = $buffered;
		}
		$this->Init();
	}

	function Init() {
	}

	function clearBuffer() {
		$this->_buffer = '';
	}

	protected function setBuffer($buff) {
		$this->_buffer = $buff;
	}

	function getBuffer() {
		return $this->_buffer;
	}

	function write($s, $attr = null) {

		$s = Utils::toString($s);
		if ($this->_idx==0) {
			echo $s;
			return ;
		}
		if ($this->_buffered) {
			$this->_buffer .= $s;
		} else {
			echo $s;
		}
	}

	function OnBuffer($buff) {
		$this->_buff [$this->_idx] = $buff;
	}

	function StartBuffer() {
		$this->_idx ++;
		if ($this->_buffered) {
			ob_start(array(
			$this,
				"OnBuffer"), null, true);
		}
	}

	function EndBuffer($flush = false) {
		if (!$this->_buffered) {
			return;
		}
		if ($this->_idx == 0) {
			return;
		}
		$r = @ob_get_clean();

		$r = null;
		$s = array();
		foreach ( $this->_buff as $k => $v ) {
			if ($k !== $this->_idx) {
				$s [$k] = $v;
			} else {
				$r = $v;
			}
		}
		$this->_idx --;
		$this->_buff = $s;
		if ($flush) {

			$this->write($r);
			$this->flush();
		}
		return $r;
	}

	function flush() {
		if (!$this->_buffered) {
			return;
		}
		$r = $this->_buffer;
		while ( $this->_idx > 0 ) {
			$r .= $this->EndBuffer();
		}
		echo $r;
		$this->_buffer =null;
	}
	
	function Render($return = false) {
		if (Request::isJSONRequest()) {
			return JSON::encode($this->_getInternal());
		}
	}
}
?>
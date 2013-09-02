<?php
namespace System;
use \System\JSON\JSON;
use \Request;
use \Utils;

abstract class AbstractResponse extends \BaseObject implements \IResponse, \IRenderable {
	private $_buffered = true;
	private $_buffer = null;
	private $_buff = array();
	private $_idx = -1;
	protected $flushed =false;
	/**
	 * @param bool|array $buffered
	 */
	function __construct($buffered = true) {
		if (is_array($buffered)) {
			foreach ($buffered as $k => $v) {
				$this->$k = $v;
			}
		} else {
			$this->_buffered = $buffered;
		}
		$this->Init();
	}
	function __destruct() {
		$this->EndBuffer(false);
	}
	function Init() {
	}
	function clearBuffer() {
		$this->_buffer = '';
	}
	/*protected function setBuffer($buff) {
		$this->_buffer = $buff;
	}*/
	function setBuffered($value) {
		if ($this->_buffered !== $value) {
			$this->_buffered = $value;
			$this->clearBuffer();
			$this->flush();
			$this->StartBuffer();
		}
		return $this;
	}
	function getBuffer() {
		return $this->_buffer;
	}
	function write($s, $attr = null) {
		$s = \Convert::toString($s);
		echo $s;
        return null;
	}
	function OnBuffer($buff,$flags) {
		$this->_buff[$this->_idx] = $buff;
	}
	function StartBuffer() {
		$this->_idx++;
		if ($this->_buffered) {
			/*ob_start(array(
					$this,
					"OnBuffer"
			), null, true);*/
			ob_start();
		}
	}
	function EndBuffer($flush = false) {
		if (!$this->_buffered) {
			return null;
		}
		if ($this->_idx < 0) {
			return null;
		}
		$r = @ob_get_clean();
		//$r = @ob_get_clean();
		/*$s = array();
		foreach ($this->_buff as $k => $v) {
		if ($k !== $this->_idx) {
		$s[$k] = $v;
		} else {
		$r = $v;
		}
		}

		$this->_buff = $s;*/
		$r .= array_pop($this->_buff);
		$this->_idx--;
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
		while ($this->_idx > 0) {
			$r .= $this->EndBuffer();
		}

		echo $r;
		while (@ob_end_flush());
		$this->_buffer = null;
		$this->flushed = true;
	}
	function Render($return = false) {
		if (Request::isJSONRequest()) {
			return JSON::encode($this->_getInternal());
		}
	}
}
?>
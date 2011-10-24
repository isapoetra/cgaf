<?php
namespace System\JSON;
use \Response;
use \Request;
class JSONResponse extends \Object implements \IRenderable {
	public $success = true;
	public $redirect;
	public $redirectInternal;
	public $metadata;
	public $results;
	public $rows;
	protected $_ignore = array();
	function setIgnore($value) {
		$this->_ignore = $value;
	}
	function getIgnore() {
		return $this->_ignore;
	}
	function __construct($code = 0, $msg = null) {
		$this->rows = array();
		$this->metadata = array(
				"totalProperty" => 'results',
				"root" => 'rows',
				"id" => 'id',
				"fields" => array(
						array(
								"name" => "msg")));
		$this->results = $code;
		$this->addMsg($msg);
	}
	function addMsg($msg, $id = null) {
		if ($msg == null) {
			return $this;
		}
		if (is_array($msg)) {
			foreach ($msg as $k => $v) {
				$this->addMsg($v, $k);
			}
		} else {
			if ($id == null) {
				$id = count($this->rows);
			}
			$this->rows[] = array(
					"id" => $id,
					"msg" => $msg);
		}
		$this->results = count($this->rows);
		return $this;
	}
	function Render($return = true) {
		Response::clearBuffer();
		$vars = get_object_vars($this);
		$o = new \stdClass();
		foreach ($vars as $k => $v) {
			if (substr($k, 0, 1) !== '_') {
				$o->$k = $v;
			}
		}
		if (Request::isJSONRequest()) {
			$retval = JSON::encode($o, false, $this->_ignore);
		} else {
			ppd($this);
		}
		if (!$return) {
			Response::Write($retval);
		}
		return $retval;
	}
	function renderDirect() {
		return $this->Render(false);
	}
}
?>
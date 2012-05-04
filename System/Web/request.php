<?php
namespace System\Web;
use System\Web\Utils\HTMLUtils;

use \CGAF as CGAF, \Utils as Utils;

class Request implements \IRequest {
	protected $_secure = array();
	private $input;
	private $_filters = array();
	private $_inputbyplace;

	function __construct($order = null) {
		$order = $order ? $order : CGAF::getConfig("request_method", "fgpc");
		$places = array(
			'f' => '_FILES',
			"g" => "_GET",
			"p" => "_POST",
			"c" => "_COOKIE");
		$order = preg_split('//', $order, -1, PREG_SPLIT_NO_EMPTY);
		foreach ($order as $current) {
			if (!array_key_exists($current, $places))
				continue;
			$varArr = $places[$current];
			$arr = null;
			eval('$arr = $' . $varArr . ';');
			if (!is_array($arr))
				continue;
			foreach ($arr as $var => $value) {
				if (is_array($value)) {
					//$this->input [$var] = $this->workArray($value);
				} else {
					foreach ($this->_filters as $filter) {
						$value = $filter->filterInput($value);
					}
				}
				if (strlen($var) < 60 && !isset($this->input[$var])) {
					if ($var !== '__url') {
						$this->_inputbyplace[$current][$var] = $value;
					}
					$this->input[$var] = $value;
				} elseif (!isset($this->input[$var])) {
					throw new \Exception('Parameter to long ' . $current . ' > ' . $var);
				}
			}
		}
		$origin = '';
		if (isset($this->input["__url"])) {
			$url = explode("/", $this->input["__url"]);
			for ($i = 0; $i < count($url); $i += 2) {
				if (isset($url[$i + 1])) {
					if (!isset($this->input[$url[$i]])) {
						$origin .= $url[$i] . '/' . $url[$i + 1];
						$this->input[$url[$i]] = $url[$i + 1];
					}
				}
			}
			$this->_origin = $origin;
			unset($this->input["__url"]);
		}
	}

	public function getOrigin() {
		return \URLHelper::add(defined('APP_URL') ? APP_URL : BASE_URL, (isset($_REQUEST['__url']) ? $_REQUEST['__url'] : $_REQUEST));
	}

	public function __get($varname) {
		if (isset($this->input[$varname])) {
			return $this->input[$varname];
		}
		return null;
	}

	/**
	 * Magic PHP Function to unset a variable
	 *
	 * @param string $varname
	 * @return void
	 */
	public function __unset($varname) {
		if (isset($this->input[$varname])) {
			unset($this->input[$varname]);
		}
	}

	function get($varName, $default = null, $secure = true, $place = null) {
		if (!is_array($varName)) {
			$r = null;
			if ($place) {
				if (isset($this->_inputbyplace[$place])) {
					$p = $this->_inputbyplace[$place];
					$r = null;
					if (isset($p[$varName])) {
						$r = $p[$varName];
						if ($secure) {
							$r = $r ? htmlentities(Utils::filterXSS($r)) : null;
						}
					}
					return $r === null || $r == "" ? $default : $r;
				}
			} else {
				$r = $secure ? $this->getSec($varName, $default) : $this->$varName;
			}
			return $r === null || $r == "" ? $default : $r;
		} else {
			$r = array();
			foreach ($varName as $var) {
				$r[$var] = $secure ? $this->getSecure($var) : $this->$var;
			}
			return $r;
		}
	}

	protected function getSec($varName, $default) {
		if (isset($this->_secure[$varName])) {
			return $this->_secure[$varName];
		}
		$retval = $this->$varName;
		$this->_secure[$varName] = is_string($retval) ? HTMLUtils::removeTag($retval) : $retval;

		return $this->_secure[$varName] !== null ? $this->_secure[$varName] : $default;
	}

	function getSecure($varName, $default = null) {
		return isset($this->_secure[$varName]) ? $this->_secure[$varName] : $this->getSec($varName, $default);
	}

	public function set($varName, $value) {
		if (is_array($varName) && $value === null) {
			foreach ($varName as $k => $v) {
				$this->set($k, $v);
			}
			return $this;
		}
		$_GET[$varName] = $value;
		$this->_secure[$varName] = $value;
		$this->input[$varName] = $value;
		$this->$varName = $value;
		return $this;
	}

	function gets($place = null, $secure = true, $ignoreEmpty = false) {
		if (is_array($place)) {
			$r = array();
			foreach ($place as $p) {
				$x = null;
				if ($secure) {
					$retval = $this->getSecure($p);
				} else {
					$retval = $this->get($p);
				}
				if (empty($x) && $ignoreEmpty)
					continue;
				$retval[$p] = $x;
			}
			return $retval;
		} elseif ($place !== null) {
			if (!isset($this->_inputbyplace[$place])) {
				return array();
			}
			if ($secure) {
				$p = $this->_inputbyplace[$place];
				$retval = array();
				if ($p) {
					foreach ($p as $k => $v) {
						$v = $this->getSecure($k);
						if ($v) {
							$retval[$k] = $v;
						}
					}
				}
			} else {
				$retval = $this->_inputbyplace[$place];
			}
			return $retval;
		} else {
			$retval = array();
			if ($this->input) {
				foreach ($this->input as $k => $v) {
					if (empty($v) && $ignoreEmpty)
						continue;
					$retval[$k] = $v;
				}
			}
		}
		return $retval;
	}
}

?>

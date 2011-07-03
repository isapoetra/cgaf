<?php
class JSONResult implements IRenderable {
	private $_code;
	private $_msg;
	private $_redirect;
	private $_vars = array ();
	function __construct($code, $msg, $redirect = null, $vars = array()) {
		$this->_code = $code;
		$this->_msg = $msg;
		$this->_redirect = $redirect;
		$this->_vars = $vars;
	}
	function getCode() {
		return $this->_code;
	}
	function setVar($key, $val) {
		$this->_vars [$key] = $val;
	}
	function Render($return = false) {
		$msg = is_string ( $this->_msg ) ? __ ( $this->_msg ) : $this->_msg;
		$retval = null;
		if (Request::isJSONRequest ()) {
			$retval = array ("_result" => $this->_code, "message" => $msg );
			if ($this->_redirect) {
				$retval ['_redirect'] = $this->_redirect;
			}
			if (is_array ( $this->_vars ) && count ( $this->_vars )) {
				$retval = array_merge_recursive ( $retval, $this->_vars );
			}
			$retval = JSON::encodeConfig ( $retval );
		} else {
			if ($this->_redirect) {
				return Response::Redirect ( $this->_redirect );
			}
			$retval .= $msg;
			foreach ( $this->_vars as $k => $v ) {
				$retval .= '<div class="result-' . $k . '">';
				if (is_array ( $v )) {
					$retval .= '<ul>';
					foreach ( $v as $j ) {
						$retval .= '<li>' . $j . '</li>';
					}
					$retval .= '</ul>';
				}
				$retval .= '</div>';
			
			}
		}
		if (! $return) {
			Response::write ( $retval );
		}
		return $retval;
	}
}
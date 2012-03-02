<?php
use System\JSON\JSON;
/**
 *
 * @author e1
 * @deprecated
 *
 */
class TResponseResult extends BaseObject implements IRenderable {
	function __construct($result, $msg) {
		$this->_result = $result;
		$this->_message = __ ( $msg );
	}
	function Render($return = false) {
		if (Request::isJSONRequest ()) {
			return JSON::encode ( $this->_internal );
		}
		return $this->_message;
	}
}
<?php
namespace System\Exceptions;

class AccessDeniedException extends SystemException {
	function __construct($msg = null) {

		$msg = $msg !== null ? $msg : "access denied";
		parent::__construct($msg);
	}

}

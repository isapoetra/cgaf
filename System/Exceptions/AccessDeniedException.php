<?php
namespace System\Exceptions;

class AccessDeniedException extends SystemException {
	function __construct($msg = null) {
		$arg = func_get_args();
		$msg = __(array_shift($arg));
		if (!$msg) {
			$msg = "access denied";
		}
		if ($arg) {
			$msg = @vsprintf($msg, $arg);
		}


		parent::__construct($msg);
	}

}

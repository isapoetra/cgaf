<?php
namespace System\Exceptions;
class CGAFException extends \Exception {
	function __construct($msgs) {
		$arg = func_get_args();
		$msg = __(array_shift($arg));
		if ($arg) {
			$msg = @vsprintf($msg, $arg);
		}
		//Logger::write($msg,E_ERROR,false);
		parent::__construct($msg);
	}
}
?>
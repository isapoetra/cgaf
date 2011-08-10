<?php
namespace System\Exceptions;
class UnimplementedException extends InvalidOperationException{
	function __construct($message="Unimplemented") {
		parent::__construct($message);
	}
}
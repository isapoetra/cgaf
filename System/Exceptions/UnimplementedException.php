<?php
class UnimplementedException extends InvalidOperationException{
	function __construct($message="Unimplemented") {
		parent::__construct($message);
	}
}
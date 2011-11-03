<?php
namespace System\Session;
use System\Events\Event;
class SessionEvent extends Event {
	const SESSION_STARTED = 'started';
	const SESSION_CLOSE = 'close';
	const SESSION_GC = 'gc';
	const DESTROY = 'destroy';
	function __construct(SessionBase $sender, $type, $args = null) {
		parent::__construct($sender, $type, $args);
	}
}

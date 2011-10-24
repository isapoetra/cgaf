<?php
namespace System\Events;

class Event {
	public $type;
	public $sender;
	function __construct($sender, $type) {
		$this->type = $type;
		$this->sender = $sender;
	}
	public static function eventLoadHandler($c) {
		if (substr($c, strlen($c) - 5) == 'Event') {
			return using('System.events.' . $c);
		}
		return false;
	}
}

?>
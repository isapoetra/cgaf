<?php
namespace System\Events;
class Event {
	public $type;
	public $sender;
	public $args;
	function __construct($sender, $type, $args = null) {
		$this->type = $type;
		$this->sender = $sender;
		$this->args = $args;
	}
	public static function eventLoadHandler($c) {
		if (substr($c, strlen($c) - 5) == 'Event') {
			return using('System.events.' . $c);
		}
		return false;
	}
}
?>
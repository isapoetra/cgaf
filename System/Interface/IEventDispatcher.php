<?php

interface IEventDispatcher {
	function addEventListener($type,$callback);
	function RemoveEventListener($type,$callback);
	function dispatchEvent($event);
}

?>
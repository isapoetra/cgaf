<?php
using('System.events.Event');
class SessionEvent extends Event{
	const SESSION_STARTED='started';
	const SESSION_CLOSE='close';
	const SESSION_GC='gc';
	const DESTROY='destroy';

}

?>
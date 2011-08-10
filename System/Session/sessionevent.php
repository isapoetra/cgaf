<?php
namespace System\Session;
class SessionEvent extends \System\Events\Event {
	const SESSION_STARTED='started';
	const SESSION_CLOSE='close';
	const SESSION_GC='gc';
	const DESTROY='destroy'; 
}
<?php
using('System.events.Event');

class LoginEvent extends Event {
	const LOGIN='autheventlogin';
	const LOGOUT='autheventlogout';
}

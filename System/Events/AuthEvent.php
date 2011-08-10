<?php
namespace System\Events;

class AuthEvent extends Event {
	const LOGIN = 'autheventlogin';
	const LOGOUT = 'autheventlogout';
}
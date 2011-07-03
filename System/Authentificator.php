<?php
defined ( "CGAF" ) or die ( "Restricted Access" );

abstract class Authentificator implements IAuthentificator {
	private $_appOwner;
	private $_lastError = null;
	function  __construct($appOwner) {
		$this->_appOwner = $appOwner;
	}
	public function getLastError() {
		return $this->_lastError;
	}
	function Authenticate($args = null) {
		$req = array ("username" => "", "password" => "", "remember" => "" );
		if ($args == null) {
			$args = Request::gets ();
		}
		$err = array ();
		foreach ( $args as $k => $v ) {
			if (array_key_exists ( $k, $req )) {
				if (empty ( $v )) {
					$err [] = "Empty $k";
				} else {
					$req [$k] = $v;
				}
			}
		}
		if (count ( $err )) {
			CGAF::addMessage ( "Authentification Error" );
			CGAF::addMessage ( $err );
			return false;
		}else{
			$c =new stdClass();
			$c->user_id=-1;
			$c->user_name=$args['username'];
			return $c;
		}
		return true;
	}
	/**
	 *
	 */
	public function Logout() {

	}

}
?>
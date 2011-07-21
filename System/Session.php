<?php
if (! defined ( "CGAF" ))
	die ( "Restricted Access" );
using ( 'System.events.SessionEvent' );

abstract class Session {
	private static $_instance;
	const STATE_EXPIRED ='expired';
	const STATE_ERROR='error';
	const STATE_ACTIVE='active';
	const STATE_CLOSED='closed';
	const STATE_DESTROYED='destroyed';
	const STATE_RESTART='restart';
	/**
	 * get Session Instance
	 *
	 * @return ISession
	 */
	public static function getInstance() {
		
		if (self::$_instance == null) {
			$configs = CGAF::getConfigs ( 'Session.configs' );
			if ($configs) {
				foreach ( $configs as $k => $v ) {
					ini_set ( 'session.' . $k, $v );
				}
			}
			
			
			$handler = CGAF::getConfig ( "Session.Handler", "File" );
			if (! class_exists ( $handler, false )) {
				CGAF::Using ( "System.Session.$handler" );
				$handler = CGAF_CLASS_PREFIX . $handler . "Session";
			}
			self::$_instance = new $handler ();
			session_set_save_handler ( array (
					&self::$_instance, 
					'open' ), array (
					&self::$_instance, 
					'close' ), array (
					&self::$_instance, 
					'read' ), array (
					&self::$_instance, 
					'write' ), array (
					&self::$_instance, 
					'destroy' ), array (
					&self::$_instance, 
					'gc' ) );
		}
		return self::$_instance;
	}

	public static function get($name, $default = null) {
		return self::getInstance ()->get ( $name, $default );
	}

	public static function set($name, $value) {
		if (CGAF_CONTEXT == "Web" && $name == "__appId" && ! self::getInstance ()->isStarted ()) {
			setcookie ( "__appId", $value );
		}
		return self::getInstance ()->set ( $name, $value );
	}

	public static function remove($varname) {
		return self::getInstance ()->remove ( $varname );
	}

	public static function Start() {
		
		$instance = self::getInstance ();
		
		if ($instance->isStarted ()) {
			return true;
		}
		return self::getInstance ()->Start ();
	}

	public static function getId() {
		return self::getInstance ()->getId ();
	}

	public static function destroy() {
		if (self::$_instance) {
			self::$_instance->destroy ();
		}
	}

	public static function registerState($stateGroup) {
		return self::getInstance ()->registerState ( $stateGroup );
	}

	public static function setState($stateGroup, $stateName, $default = null) {
		return self::getInstance ()->setState ( $stateGroup, $stateName, $default );
	
	}

	public static function getState($stateGroup, $stateName, $default = null) {
		return self::getInstance ()->getState ( $stateGroup, $stateName, $default );
	
	}
}
?>
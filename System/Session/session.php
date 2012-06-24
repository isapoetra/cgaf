<?php
namespace System\Session;
use System\DB\DBQuery;

if (!defined("CGAF"))
	die("Restricted Access");
use \CGAF as CGAF;

abstract class Session {
  /**
   * @var \ISession
   */
	private static $_instance;
	const STATE_EXPIRED = 'expired';
	const STATE_ERROR = 'error';
	const STATE_ACTIVE = 'active';
	const STATE_CLOSED = 'closed';
	const STATE_DESTROYED = 'destroyed';
	const STATE_RESTART = 'restart';
	/**
	 * get Session Instance
	 *
	 * @return \ISession|\IEventDispatcher
	 */
	public static function getInstance() {
		if (self::$_instance == null) {
			$configs = CGAF::getConfigs('Session.configs');
			if ($configs) {
				foreach ($configs as $k => $v) {
					ini_set('session.' . $k, $v);
				}
			}
			$handler = CGAF::getConfig("Session.Storage", "File");
			if (!class_exists($handler, false)) {
				$handler = 'System\\Session\\Storage\\' . $handler;
			}
			self::$_instance = new $handler();
			session_set_save_handler(array(
					&self::$_instance,
					'open'
			), array(
					&self::$_instance,
					'close'
			), array(
					&self::$_instance,
					'read'
			), array(
					&self::$_instance,
					'write'
			), array(
					&self::$_instance,
					'destroy'
			), array(
					&self::$_instance,
					'gc'
			));
		}
		return self::$_instance;
	}
	public static function &get($name, $default = null) {
		return self::getInstance()->get($name, $default);
	}
	public static function set($name, $value) {
		if (CGAF_CONTEXT == "Web" && $name == "__appId" && !self::getInstance()->isStarted()) {
			setcookie("__appId", $value);
		}
		if (self::$_instance) {
			return self::getInstance()->set($name, $value);
		}
    return $value;
	}
	public static function remove($varname) {
		return self::getInstance()->remove($varname);
	}
	public static function Start() {
		$instance = self::getInstance();
		if ($instance->isStarted()) {
			return true;
		}
		return self::getInstance()->Start();
	}
	public static function reStart() {
		return self::getInstance()->reStart();
	}
	public static function getId() {
		return self::getInstance()->getId();
	}
	public static function destroy() {
		if (self::$_instance) {
			self::$_instance->destroy();
		}
	}
	private static function destroyById($id){
		if (self::$_instance) {
			return self::$_instance->destroy($id);
		}
	}
	public static function registerState($stateGroup) {
		return self::getInstance()->registerState($stateGroup);
	}
	public static function unregisterState($stateGroup) {
		return self::getInstance()->unregisterState($stateGroup);
	}
	public static function setState($stateGroup, $stateName, $default = null) {
		return self::getInstance()->setState($stateGroup, $stateName, $default);
	}
	public static function setStates(sessionStateHandler $state) {
		return self::getInstance()->setStates($state);
	}
	public static function getStates() {
		return self::getInstance()->getStates();
	}
	public static function getState($stateGroup, $stateName, $default = null) {
		return self::getInstance()->getState($stateGroup, $stateName, $default);
	}
	public static function clearPast() {
		$lifetime = self::getInstance()->getConfig('gc_maxlifetime');
		$past = time() - $lifetime;
		$q= new DBQuery(CGAF::getDBConnection());
		$q
		->clear()
		->addTable('session');
		$rows=$q->where('last_access < ' . $q->quote($q->toDate($past)))->loadObjects();
		if ($rows) {
			foreach($rows as $row) {
				if (self::destroyById($row->session_id)) {
					$q->clear('where')
					->Where('session_id='.$q->quote($row->session_id))
					->delete()
					->exec();
				}
			}
		}
	}
}
?>
<?php
namespace System\Session;
use \CGAF as CGAF, \Utils as Utils;
class sessionStateHandler extends \ArrayObject {
	function getState($name, $def = null) {
		if (is_array($name)) {
			foreach ($name as $n) {
			}
			return $this;
		}
		return isset($this[$name]) ? $this[$name] : $def;
	}
	function setState($name,$value) {
		$this[$name] = $value;
	}
}
abstract class SessionBase extends \Object implements \ISession {
	private $_started = false;
	private $_sessionState = 'active';
	private $_configs;
	function __construct() {
		//Need to destroy any existing sessions started with session.auto_start
		if (session_id()) {
			session_unset();
			session_destroy();
		}
		$this->_configs = CGAF::getConfigs('Session');
		//set default sessios save handler
		ini_set('session.save_handler', 'files');
		//disable transparent sid support
		ini_set('session.use_trans_sid', '0');
	}
	protected function getSessionState() {
		return $this->_sessionState;
	}
	public function unregisterState($stateGroup) {
		$state = $this->get('__state');
		if (isset($state[$stateGroup])) {
			unset($state[$stateGroup]);
		}
	}
	public function &registerState($stateGroup) {
		$state = $this->get('__state');
		if (!$state) {
			$state = new sessionStateHandler();
			$this->set('__state', $state);
		}
		$state = $this->get('__state');
		if (!isset($state[$stateGroup])) {
			$state[$stateGroup] = new sessionStateHandler();
			$this->set('__state', $state);
		}
		$state = $state[$stateGroup];
		return $state;
	}
	public function &getStates() {
		return $this->get('__state');
	}
	public function &setStates(sessionStateHandler $state) {
		if ($state) {
			$this->set('__state', $state);
		}
		return $state;
	}
	public function setState($stateGroup, $stateName, $value) {
		//$this->set ( '__state', null );
		$state = $this->registerState($stateGroup);
		return $state->setState($stateName, $value);
	}
	public function getState($stateGroup, $stateName, $default = null) {
		//$this->set ( '__state', null );
		$state = $this->registerState($stateGroup);
		return $state->getState($stateName, $default);
	}
	protected function _createId() {
		$id = 0;
		while (strlen($id) < 32) {
			$id .= mt_rand(0, mt_getrandmax());
		}
		$id = md5(uniqid($id, true));
		return $id;
	}
	function getId() {
		return session_id();
	}
	function getConfig($name, $def = null) {
		$r = Utils::findConfig($name, $this->_configs);
		if ($r == null) {
			$r = ini_get('session.' . $name);
			if (!$r) {
				$r = $def;
			}
		}
		return $r;
	}
	function __destruct() {
		$this->close();
	}
	private function _setCounter() {
		$counter = $this->get('session.counter', 0);
		++$counter;
		$this->set('session.counter', $counter);
		return true;
	}
	/**
	 * @param unknown_type $restart
	 */
	public function Start() {
		if ($this->isStarted()) {
			return true;
		}
		// Send modified header for IE 6.0 Security Policy
		header('P3P: CP="NOI ADM DEV PSAi COM NAV OUR OTRo STP IND DEM"');
		$id = null;
		$restart = false;
		if ($this->_sessionState == 'restart') {
			$id = $this->_createId();
			$restart = true;
		}
		session_name('CGAFSESS');
		$sess = session_name();
		session_cache_limiter('nocache');
		$new = false;
		if (!$id) {
			if (isset($_POST[$sess])) {
				$id = $_POST[$sess];
			} else if (isset($_GET[$sess])) {
				$id = $_GET[$sess];
			} else if (isset($_COOKIE[$sess])) {
				$id = $_COOKIE[$sess];
			} else {
				$new = true;
				$id = $this->_createId();
			}
		}
		if ($id) {
			session_id($id);
		}
		//sync the session maxlifetime
		$this->_setCookieParams();
		session_start();
		$this->_sessionState = Session::STATE_ACTIVE;
		//initialise the session
		$this->_setCounter();
		$this->_setTimers();
		// perform security checks
		if (!$this->_validate($restart, $new)) {
			throw new \Exception('Security Violation');
			return $this->restart();
		}
		$this->_started = true;
		if (!$restart) {
			$this->dispatchEvent(new SessionEvent($this, SessionEvent::SESSION_STARTED));
		}
	}
	private function _setTimers() {
		if (!$this->get('session.timer.start')) {
			$start = time();
			$this->set('session.oriurl', $_SERVER['REQUEST_URI']);
			$this->set('session.timer.start', $start);
			$this->set('session.timer.last', $start);
			$this->set('session.timer.now', $start);
		}
		$this->set('session.timer.last', $this->get('session.timer.now'));
		$this->set('session.timer.now', time());
		return true;
	}
	private function _setCookieParams() {
		ini_set('session.gc_maxlifetime', $this->getConfig('gc_maxlifetime'));
		$cookie = session_get_cookie_params();
		if ($this->_force_ssl) {
			$cookie['secure'] = true;
		}
		if ($this->getConfig('cookie.domain', '') != '') {
			$cookie['domain'] = $this->getConfig('cookie.domain');
		}
		if ($this->getConfig('cookie.path', '') != '') {
			$cookie['path'] = $this->getConfig('cookie.path');
		}
		//$expire = ($this->getConfig('gc_maxlifetime'));
		//$expire = gmdate("D, d M Y H:i:s", time() + date("Z") + $expire) . ' GMT';
		//problem with timezone
		$expire = time() + (date("Z") + $this->getConfig('gc_maxlifetime'));
		$cookie['lifetime'] = 0;
		$ses = session_name();
		setcookie($ses, "", time() - 3600);
		setcookie($ses, $this->getId(), $cookie['lifetime'], $cookie['path'], $cookie['domain'], $cookie['secure']);
		//session_set_cookie_params($cookie['lifetime'], $cookie['path'], $cookie['domain'], $cookie['secure']);
		//ppd(date('D, d M Y H:i:s', $expire));
	}
	protected function _validate($restart = false) {
		// allow to restart a session
		if ($restart) {
			$this->_sessionState = 'active';
			$this->set('session.client.address', null);
			$this->set('session.client.forwarded', null);
			$this->set('session.client.browser', null);
			$this->set('session.token', null);
		}
		// check if session has expired
		if ($this->getConfig('gc_maxlifetime')) {
			$curTime = $this->get('session.timer.now', 0);
			$maxTime = $this->get('session.timer.last', 0) + ($this->getConfig('gc_maxlifetime'));
			// empty session variables
			if ($maxTime < $curTime) {
				$this->restart();
			}
		}
		// record proxy forwarded for in the session in case we need it later
		if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
			$this->set('session.client.forwarded', $_SERVER['HTTP_X_FORWARDED_FOR']);
		}
		$security = $this->getConfig('security', array());
		// check for client adress
		//$this->set('session.remoteaddress','225232.');
		if (isset($security['fix_address']) && $security['fix_address'] && isset($_SERVER['REMOTE_ADDR'])) {
			$ip = $this->get('session.remoteaddress');
			if ($ip === null) {
				$this->set('session.remoteaddress', $_SERVER['REMOTE_ADDR']);
			} else if ($_SERVER['REMOTE_ADDR'] !== $ip) {
				$this->_sessionState = Session::STATE_ERROR;
				return false;
			}
		}
		// check for clients browser
		if (isset($security['fix_browser']) && $security['fix_browser'] && isset($_SERVER['HTTP_USER_AGENT'])) {
			$browser = $this->get('session.client.browser');
			if ($browser === null) {
				$this->set('session.client.browser', $_SERVER['HTTP_USER_AGENT']);
			} else if ($_SERVER['HTTP_USER_AGENT'] !== $browser) {
				$this->_sessionState = Session::STATE_ERROR;
				ppd($_SERVER['HTTP_USER_AGENT'] . $browser);
				return false;
			}
		}
		return true;
	}
	/**
	 *
	 */
	public function isStarted() {
		return $this->_started;
	}
	/**
	 * @param String $name
	 * @param unknown_type $default
	 */
	public function &get($name, $default = null) {
		if ($this->_sessionState !== 'active' && $this->_sessionState !== 'expired') {
			$this->restart();
		}
		if (isset($_SESSION['__session'][$name])) {
			return $_SESSION['__session'][$name];
		}
		return $default;
	}
	/**
	 * @param unknown_type $name
	 * @param unknown_type $value
	 */
	public function set($name, $value) {
		if ($this->_sessionState !== Session::STATE_ACTIVE) {
			return null;
		}
		$old = isset($_SESSION['__session'][$name]) ? $_SESSION['__session'][$name] : null;
		if (null === $value) {
			unset($_SESSION['__session'][$name]);
		} else {
			$_SESSION['__session'][$name] = $value;
		}
		return $old;
	}
	/**
	 * @param unknown_type $varname
	 */
	public function remove($varname) {
		if (!isset($_SESSION['__session'][$varname])) {
			return;
		}
		$old = $_SESSION['__session'][$varname];
		unset($_SESSION['__session'][$varname]);
		return $old;
	}
	function close() {
		if ($this->_sessionState === Session::STATE_CLOSED) {
			return false;
		}
		$this->_sessionState = Session::STATE_CLOSED;
		session_write_close();
	}
	/**
	 * @param unknown_type $sessID
	 */
	public function destroy($sessID = null) {
		$sessID = $sessID ? $sessID : $this->getId();
		$this->dispatchEvent(new SessionEvent($this, SessionEvent::DESTROY));
		// session was already destroyed
		if ($this->_sessionState === Session::STATE_DESTROYED) {
			return true;
		}
		// In order to kill the session altogether, like to log the user out, the session id
		// must also be unset. If a cookie is used to propagate the session id (default behavior),
		// then the session cookie must be deleted.
		if (isset($_COOKIE[session_name()])) {
			$cookie_domain = $this->getConfig('cookie.domain', '');
			$cookie_path = $this->getConfig('cookie.path', '/');
			setcookie(session_name(), '', time() - 42000, $cookie_path, $cookie_domain);
		}
		$this->_sessionState = Session::STATE_DESTROYED;
		session_unset();
		@session_destroy();
		$this->_started = false;
		return true;
	}
	function restart() {
		$this->destroy();
		if ($this->_sessionState !== Session::STATE_DESTROYED && $this->_sessionState !== Session::STATE_CLOSED) {
			return false;
		}
		$this->_sessionState = Session::STATE_RESTART;
		//regenerate session id
		$id = $this->_createId(strlen($this->getId()));
		session_id($id);
		$this->_started = false;
		$this->start();
		$this->_sessionState = Session::STATE_ACTIVE;
		$this->_validate();
		$this->_setCounter();
		return true;
	}
	function gc($sessMaxLifeTime) {
		$this->dispatchEvent(new SessionEvent($this, SessionEvent::SESSION_GC));
		return true;
	}
}
?>
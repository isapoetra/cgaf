<?php
use System\Exceptions\InvalidOperationException;
use System\Exceptions\SystemException;
interface IEventDispatcher {
	function addEventListener($type, $callback);
	function RemoveEventListener($type, $callback);
	function dispatchEvent($event);
}
interface IObject {
	/**
	 * getAllowed properties
	 * @return array
	 */
	function setValue($value);
	function getValue();
	function assign($var, $val = null);
}
class Object extends \stdClass implements IObject, IEventDispatcher {
	protected $_value;
	protected $_name;
	protected $_e = array();
	protected $_internal = array();
	protected $_strict;
	protected $_param;
	protected $_sender;
	private $_reflection;
	private $_lastError;
	protected $_events = array();
	/**
	 * TGObject::__construct()
	 *
	 * @param mixed $param
	 * @return
	 */
	function __construct($param = null) {
		/*if (! isset($this->_name)) {
		 $this->_name = get_class($this);
		}*/
		//$this->addEventHandler("prepareOutput");
		$this->_param = $param;
	}
	function __destruct() {
		$this->_events = array();
		$this->_internal = array();
	}
	function addEventListener($type, $callback) {
		if (is_callable($callback)) {
			$this->_events[$type][] = $callback;
		}else{
			throw new SystemException('invalid callback handler');
		}
	}
	protected function addEventHandler($name) {
		if (is_string($name)) {
			$name = strtolower($name);
			if (!isset($this->_e[$name])) {
				$this->_e[$name] = array();
			}
		} elseif (is_array($name)) {
			foreach ($name as $namex) {
				if (is_string($name)) {
					$this->addEvent($namex);
				} else {
					throw new SystemException("Unkhown Event");
				}
			}
		} else {
			throw new SystemException("Unkhown Event");
		}
	}
	function RemoveEventListener($type, $callback) {
		foreach ($this->_events[$type] as $k => $v) {
			if ($v == $callback) {
				unset($this->_events[$type][$k]);
			}
		}
	}
	function __call($name, $a) {
		throw new Exception('Undefined Method ' . $name . ' In Class ' . get_class($this));
	}
	function dispatchEvent($event) {
		if (!CGAF::isInitialized()) {
			return;
		}
		foreach ($this->_events as $k => $v) {
			if ($k == '*' || $k == $event->type) {
				foreach ($v as $f) {
					call_user_func($f, $event);
				}
			}
		}
	}
	function isInstanceOf($sub, $super) {
		$sub = is_object($sub) ? get_class($sub) : (string) $sub;
		$super = is_object($super) ? get_class($super) : (string) $super;
		switch (true) {
		case $sub === $super: // well ... conformity
		case is_subclass_of($sub, $super):
		case in_array($super, class_implements($sub)):
			return true;
		default:
			return false;
		}
	}
	/**
	 * TGObject::__get()
	 *
	 * @param mixed $name
	 * @return
	 */
	function __get($name) {
		$getter = 'get' . $name;
		if (method_exists($this, $getter)) {
			// getting a property
			return $this->$getter();
		} else {
			if (strncasecmp($name, 'on', 2) === 0 && method_exists($this, $name)) {
				// getting an event (handler list)
				$name = strtolower($name);
				if (!isset($this->_e[$name]))
					$this->_e[$name] = new System\Collections\Collection();
				return $this->_e[$name];
			} else {
				if (!$this->_strict) {
					$name = strtolower($name);
					$val = $this->_getInternal($name);
					return $val;
				} else {
					throw new SystemException("Invalid property $name for object " . get_class($this));
				}
			}
		}
	}
	protected function _getInternal($name = null) {
		if ($name == null) {
			return $this->_internal;
		}
		return isset($this->_internal[$name]) ? $this->_internal[$name] : null;
	}
	protected function setLastError($value) {
		$this->_lastError = $value;
	}
	public function getLastError() {
		return $this->_lastError;
	}
	/**
	 * TGObject::__set()
	 *
	 * @param mixed $name
	 * @param mixed $value
	 * @return
	 */
	function __set($name, $value) {
		$setter = 'set' . $name;
		if (method_exists($this, $setter)) {
			return $this->$setter($value);
		} else if (strncasecmp($name, 'on', 2) === 0 && method_exists($this, $name)) {
			$this->attachEventHandler($name, $value);
		} else {
			if (method_exists($this, 'get' . $name)) {
				if (isset($this->_interna["$name"])) {
					$this->_internal[$name] = $value;
				} else {
					throw new InvalidOperationException('error.component_property_readonly', $name, get_class($this));
				}
			} else {
				if (!$this->_strict) {
					$name = strtolower($name);
					$this->_internal[$name] = $value;
				} else {
					throw new InvalidOperationException('component_property_undefined', $name, get_class($this));
				}
			}
		}
		return $value;
	}
	/**
	 * TGObject::setValue()
	 *
	 * @param mixed $value
	 * @return
	 */
	function setValue($value) {
		$this->_value = $value;
	}
	/**
	 * TGObject::getValue()
	 *
	 * @return
	 */
	function getValue() {
		return $this->_value;
	}
	function getName() {
		return $this->_name;
	}
	function setName($value) {
		$this->_name = $value;
	}
	//	/**
	//	 * @return boolean whether an event has been attached one or several handlers
	//	 */
	//	public function hasEventHandler($name) {
	//		$name = strtolower($name);
	//		return isset($this->_e [$name]) && $this->_e [$name]->getCount() > 0;
	//	}
	//
	//	function hasProperty($propName) {
	//		if ($this->_reflection) {
	//			$obj = new ReflectionClass($this);
	//			$this->_reflection = $obj->getProperties();
	//		}
	//		foreach ( $this->_reflection as $v ) {
	//			if ($v->name == $propName) {
	//				return true;
	//			}
	//		}
	//		$getter = 'get' . $propName;
	//		return method_exists($this, $getter);
	//	}
	//
	//	/**
	//	 * Determines whether an event is defined.
	//	 * An event is defined if the class has a method whose name is the event name prefixed with 'on'.
	//	 * Note, event name is case-insensitive.
	//	 * @param string the event name
	//	 * @return boolean
	//	 */
	//	public function hasEvent($name) {
	//		return strncasecmp($name, 'on', 2) === 0 && method_exists($this, $name);
	//	}
	//
	//	/**
	//	 * Returns the list of attached event handlers for an event.
	//	 * @return TList list of attached event handlers for an event
	//	 * @throws InvalidOperationException if the event is not defined
	//	 */
	//	public function &getEventHandlers($name) {
	//		if (strncasecmp($name, 'on', 2) === 0) {
	//			$name = strtolower($name);
	//			if (! isset($this->_e [$name]))
	//				$this->_e [$name] = array();
	//			return $this->_e [$name];
	//		} else
	//			throw new InvalidOperationException('component_event_undefined', get_class($this), $name);
	//	}
	//
	//	/**
	//	 * Attaches an event handler to an event.
	//	 *
	//	 * The handler must be a valid PHP callback, i.e., a string referring to
	//	 * a global function name, or an array containing two elements with
	//	 * the first element being an object and the second element a method name
	//	 * of the object. In Prado, you can also use method path to refer to
	//	 * an event handler. For example, array($object,'Parent.buttonClicked')
	//	 * uses a method path that refers to the method $object->Parent->buttonClicked(...).
	//	 *
	//	 * The event handler must be of the following signature,
	//	 * <code>
	//	 * function handlerName($sender,$param) {}
	//	 * </code>
	//	 * where $sender represents the object that raises the event,
	//	 * and $param is the event parameter.
	//	 *
	//	 * This is a convenient method to add an event handler.
	//	 * It is equivalent to {@link getEventHandlers}($name)->add($handler).
	//	 * For complete management of event handlers, use {@link getEventHandlers}
	//	 * to get the event handler list first, and then do various
	//	 * {@link TList} operations to append, insert or remove
	//	 * event handlers. You may also do these operations like
	//	 * getting and setting properties, e.g.,
	//	 * <code>
	//	 * $component->OnClick[]=array($object,'buttonClicked');
	//	 * $component->OnClick->insertAt(0,array($object,'buttonClicked'));
	//	 * </code>
	//	 * which are equivalent to the following
	//	 * <code>
	//	 * $component->getEventHandlers('OnClick')->add(array($object,'buttonClicked'));
	//	 * $component->getEventHandlers('OnClick')->insertAt(0,array($object,'buttonClicked'));
	//	 * </code>
	//	 *
	//	 * @param string the event name
	//	 * @param callback the event handler
	//	 * @throws InvalidOperationException if the event does not exist
	//	 */
	//	public function attachEventHandler($name, $handler) {
	//		$this->getEventHandlers($name)->add($handler);
	//	}
	//
	//	/**
	//	 * Detaches an existing event handler.
	//	 * This method is the opposite of {@link attachEventHandler}.
	//	 * @param string event name
	//	 * @param callback the event handler to be removed
	//	 * @return boolean if the removal is successful
	//	 */
	//	public function detachEventHandler($name, $handler) {
	//		if ($this->hasEventHandler($name)) {
	//			try {
	//				$this->getEventHandlers($name)->remove($handler);
	//				return true;
	//			} catch ( Exception $e ) {
	//				CGAF::trace(__FILE__, __LINE__, E_ERROR, $e->getMessage());
	//			}
	//		}
	//		return false;
	//	}
	//
	//	protected function addEventHandler($name) {
	//		if (is_string($name)) {
	//			$name = strtolower($name);
	//			if (! isset($this->_e [$name])) {
	//				$this->_e [$name] = array();
	//			}
	//		} elseif (is_array($name)) {
	//			foreach ( $name as $namex ) {
	//				if (is_string($name)) {
	//					$this->addEventHandler($namex);
	//				} else {
	//					throw new SystemException("Unkhown Event");
	//				}
	//			}
	//		} else {
	//			throw new SystemException("Unkhown Event");
	//		}
	//	}
	//
	//	/**
	//	 * Raises an event.
	//	 * This method represents the happening of an event and will
	//	 * invoke all attached event handlers for the event.
	//	 * @param string the event name
	//	 * @param mixed the event sender object
	//	 * @param TEventParameter the event parameter
	//	 * @throws InvalidOperationException if the event is undefined
	//	 * @throws TInvalidDataValueException If an event handler is invalid
	//	 */
	//	public function raiseEvent($name, $sender) {
	//		$name = strtolower($name);
	//		$param = null;
	//		//CGAF::trace(__FILE__,__LINE__,E_NOTICE,'cgaf_event_handler_raise '.$name);
	//		if (isset($this->_e [$name])) {
	//			foreach ( $this->_e [$name] as $handler ) {
	//				if (is_string($handler)) {
	//					if (($pos = strrpos($handler, '.')) !== false) {
	//						$object = $this->getSubProperty(substr($handler, 0, $pos));
	//						$method = substr($handler, $pos + 1);
	//						if (method_exists($object, $method))
	//							return $object->$method($sender, $param);
	//						else
	//							throw new InvalidOperationException('object_eventhandler_invalid', get_class($this), $name, $handler);
	//					} else {
	//						return call_user_func($handler, $sender, $param);
	//					}
	//				} else {
	//					if (is_callable($handler, true)) {
	//						// an array: 0 - object, 1 - method name/path
	//						list($object, $method) = $handler;
	//						if (is_string($object)) // static method call
	//							return call_user_func($handler, $sender, $param);
	//						else {
	//							if (($pos = strrpos($method, '.')) !== false) {
	//								$object = $this->getSubProperty(substr($method, 0, $pos));
	//								$method = substr($method, $pos + 1);
	//							}
	//							if (method_exists($object, $method)) {
	//								$params = func_get_args();
	//								array_shift($params);
	//								return call_user_func_array($handler, $params);
	//							} else
	//								throw new InvalidOperationException('component_eventhandler_invalid', get_class($this), $name, $handler [1]);
	//						}
	//					} else
	//						throw new InvalidOperationException('component_eventhandler_invalid', get_class($this), $name, gettype($handler));
	//				}
	//			}
	//			return true;
	//		} else {
	//			if (! $this->hasEvent($name)) {
	//				throw new InvalidOperationException('component_event_undefined', get_class($this), $name);
	//			}
	//		}
	//		return false;
	//	}
	function AssignTo(&$Obj) {
		$var = get_object_vars($this);
		foreach ($var as $k => $v) {
			if (!is_object($v)) {
				$Obj->$k = $v;
			}
		}
	}
	/**
	 * (non-PHPdoc)
	 * @see IObject::assign()
	 */
	function assign($var, $val = null) {
		if (is_string($var)) {
			$this->_value = (string) $val;
		} elseif ($var instanceof Object) {
			$var->AssignTo($this);
		} else {
			throw new SystemException("Cannot assign " . (is_object($val) ? get_class($val) : gettype($val)) . " into object " . get_class($this));
		}
	}
	//this function return only public variable of this class
	public function getObjectVars() {
		$vars = array_keys(get_class_vars(get_class($this)));
		$retval = new stdClass();
		foreach ($vars as $k) {
			$retval->$k = $this->$k;
		}
		return $retval;
	}
	public function toString() {
		return get_class($this);
	}
}
abstract class StaticObject {
	private static $_events = array();
	public static function bind($event, $callback) {
		$event = strtolower($event);
		if (!isset(self::$_events[$event])) {
			self::$_events[$event] = array();
		}
		self::$_events[$event][] = $callback;
	}
	public static function trigger($eventName, $args = null) {
		$eventName = strtolower($eventName);
		if (!is_array($args)) {
			$args = array(
					$args);
		}
		if (isset(self::$_events[$eventName])) {
			$events = self::$_events[$eventName];
			foreach ($events as $event) {
				call_user_func_array($event, $args);
			}
		}
	}
}
?>
<?php
use System\Exceptions\InvalidOperationException;
use System\Exceptions\SystemException;

interface IEventDispatcher {
  function addEventListener($type, $callback);

  function RemoveEventListener($type, $callback);

  function dispatchEvent($event);
}

interface IObject {
  function setValue($value);

  function getValue();

  function assign($var, $val = null);
}

class BaseObject extends \stdClass implements IObject, IEventDispatcher {
  protected $_value;
  protected $_name;
  protected $_e = array();
  protected $_internal = array();
  protected $_strict;
  protected $_param;
  protected $_sender;
  //private $_reflection;
  protected $_lastError=array();
  protected $_events = array();

  /**
   * @param null $param
   */
  function __construct($param = null) {
    $this->_param = $param;
  }

  function __destruct() {
    $this->_events = array();
    $this->_internal = array();
  }

  public function __toString() {
    return $this->toString();
  }

  function addEventListener($type, $callback) {
    if (is_callable($callback)) {
      $this->_events[$type][] = $callback;
    } else {
      throw new SystemException('invalid callback handler');
    }
  }

  /**
   * @param array|string $name
   *
   * @throws System\Exceptions\SystemException
   */
  protected function addEventHandler($name) {
    if (is_string($name)) {
      $name = strtolower($name);
      if (!isset($this->_e[$name])) {
        $this->_e[$name] = array();
      }
    } elseif (is_array($name)) {
      foreach ($name as $namex) {
        if (is_string($namex)) {
          $this->addEventHandler($namex);
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
    if (\CGAF::isDebugMode()) {
      ppd('Undefined Method ' . $name . ' In Class ' . get_class($this));
    }
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
    $sub = is_object($sub) ? get_class($sub) : (string)$sub;
    $super = is_object($super) ? get_class($super) : (string)$super;
    switch (true) {
      case $sub === $super: // well ... conformity
      case is_subclass_of($sub, $super):
      case in_array($super, class_implements($sub)):
        return true;
      default:
        return false;
    }
  }

  protected function _initGet() {
  }

  /**
   * @param $name
   *
   * @return array|null
   * @throws System\Exceptions\SystemException
   */
  function __get($name) {
    $getter = 'get' . $name;
    $this->_initGet($name);
    if (method_exists($this, $getter)) {
      // getting a property
      return $this->$getter();
    } else {
      if (strncasecmp($name, 'on', 2) === 0 && method_exists($this, $name)) {
        // getting an event (handler list)
        $name = strtolower($name);
        if (!isset($this->_e[$name])) {
          $this->_e[$name] = new System\Collections\Collection();
        }
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
	  return false;
  }

  public function getLastError() {
    return $this->_lastError;
  }

  /**
   * @param $name
   * @param $value
   *
   * @return mixed
   * @throws System\Exceptions\InvalidOperationException
   */
  function __set($name, $value) {
    $setter = 'set' . $name;
    if (method_exists($this, $setter)) {
      return $this->$setter($value);
    } else {
      if (strncasecmp($name, 'on', 2) === 0 && method_exists($this, $name)) {
        $this->addEventListener($name, $value);
      } else {
        if (method_exists($this, 'get' . $name)) {
          if (isset($this->_internal["$name"])) {
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
    }
    return $value;
  }

  /**
   * @param $value
   */
  function setValue($value) {
    $this->_value = $value;
  }

  /**
   * @return mixed
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

  function AssignTo(&$Obj) {
    $var = get_object_vars($this);
    foreach ($var as $k => $v) {
      if (!is_object($v)) {
        $Obj->$k = $v;
      }
    }
  }

  /**
   * @param   $var string|BaseObject|\object
   * @param   $val mixed|null
   *
   * @throws System\Exceptions\SystemException
   */
  function assign($var, $val = null) {
    if (is_string($var)) {
      $this->_value = (string)$val;
    } elseif ($var instanceof BaseObject) {
      $var->AssignTo($this);
    } elseif ($var instanceof \stdClass) {
      foreach ($var as $k => $v) {
        $this->$k = $v;
      }
    } else {
      throw new SystemException(
        "Cannot assign " . (is_object($val) ? get_class($val) : gettype($val)) . " into object " . get_class($this));
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

  public function __clone() {
    foreach ($this as $key => $val) {
      if (is_object($val) || (is_array($val))) {
        $this->{$key} = unserialize(serialize($val));
      }
    }
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
<?php
/**
 * User: Iwan Sapoetra
 * Date: 04/03/12
 * Time: 3:08
 */
namespace System\Session;
class sessionStateHandler extends \ArrayObject {
  function getState($name, $def = null) {
    if (is_array($name)) {
      foreach ($name as $n) {
      }
      return $this;
    }
    return isset($this[$name]) ? $this[$name] : $def;
  }
  function setState($name, $value) {
    $this[$name] = $value;
  }
}
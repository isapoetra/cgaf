<?php
/**
 * User: Iwan Sapoetra
 * Date: 04/03/12
 * Time: 3:08
 */
namespace System\Session;
class sessionStateHandler extends \ArrayObject
{
    function __construct($array = NULL)
    {
        if ($array) {
            $array = is_array($array) ? $array : \Convert::toArray($array);
            parent::__construct($array);
        } else {
            parent::__construct();
        }
    }

    function getState($name, $def = null)
    {
        if (is_array($name)) {
            foreach ($name as $n) {
            }
            return $this;
        }
        return isset($this[$name]) ? $this[$name] : $def;
    }

    function setState($name, $value)
    {
        $this[$name] = $value;
    }
}
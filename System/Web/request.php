<?php
namespace System\Web;

use CGAF as CGAF;
use System\Web\Security\IWebFilterInput;
use System\Web\Security\Security;
use System\Web\Utils\HTMLUtils;
use Utils as Utils;

class Request implements \IRequest
{
    public $_securityFilters = array(
        'filterxss'
    );
    protected $_secure = array();
    private $input;
    private $_filters = array();
    private $_inputbyplace;
    private $_ignore = array(
        '_gauges_unique_month',
        '_gauges_unique_year',
        '_gauges_unique',
        '_gauges_unique_day',
        '_gauges_unique_hour'

    );

    function __construct($order = null)
    {
        $order = $order ? $order : CGAF::getConfig("request_method", "fgpc");
        $places = array(
            'f' => '_FILES',
            "g" => "_GET",
            "p" => "_POST",
            "c" => "_COOKIE");
        $order = preg_split('//', $order, -1, PREG_SPLIT_NO_EMPTY);
        foreach ($order as $current) {
            if (!array_key_exists($current, $places))
                continue;
            $varArr = $places[$current];
            $arr = null;
            eval('$arr = $' . $varArr . ';');
            if (!is_array($arr))
                continue;
            foreach ($arr as $var => $value) {
                if (in_array($var, $this->_ignore)) continue;
                if (!is_array($value)) {
                    /**
                     * @var IWebFilterInput $filter
                     */
                    foreach ($this->_filters as $filter) {
                        $value = $filter->filterInput($value);
                    }
                }

                if (strlen($var) < 60) {
                    if ($var !== '__url') {
                        $this->_inputbyplace[$current][$var] = $value;
                    }
                    //if (!isset($this->input[$var])) {
                    $this->input[$var] = $value;
                    //}
                } elseif (!isset($this->input[$var])) {
                    throw new \Exception('Parameter to long ' . $current . ' > ' . $var);
                }
            }
        }
    }
    public  function addFilterInput($name) {
        if (!in_array($name,$this->_filters)) {
            $this->_filters[] = $name;
        }
    }
    public  function addSecurityFilter($name) {
        if (!in_array($name,$this->_securityFilters)) {
            $this->_securityFilters[] = $name;
        }
    }
    public function __get($varname)
    {
        if (isset($this->input[$varname])) {
            return $this->input[$varname];
        }
        return null;
    }

    /**
     * Magic PHP Function to unset a variable
     *
     * @param string $varname
     * @return void
     */
    public function __unset($varname)
    {
        if (isset($this->input[$varname])) {
            unset($this->input[$varname]);
        }
    }

    function get($varName, $default = null, $secure = true, $place = null)
    {
        if (!is_array($varName)) {
            $r = null;
            if ($place) {
                if (isset($this->_inputbyplace[$place])) {
                    $p = $this->_inputbyplace[$place];
                    $r = null;
                    if (isset($p[$varName])) {
                        $r = $p[$varName];
                        if ($secure) {
                            $r = $r ? self::getSecure($varName, $default) : null; // htmlentities(Utils::filterXSS($r))
                        }
                    }
                    return $r === null || $r == "" ? $default : $r;
                }
            } else {
                $r = $secure ? $this->getSecure($varName, $default) : $this->$varName;
            }
            return $r === null || $r == "" ? $default : $r;
        } else {
            $r = array();
            foreach ($varName as $var) {
                $r[$var] = $secure ? $this->getSecure($var) : $this->$var;
            }
            return $r;
        }
    }

    protected function getSecure($varName, $default = null, $place = null)
    {
        if (isset($this->_secure[$varName])) {
            return $this->_secure[$varName];
        }
        $retval = $this->get($varName, $default, false, $place);
        $this->_secure[$varName] = $this->secureVar($retval); //is_string($retval) ? HTMLUtils::removeTag($retval) : $retval;

        return $this->_secure[$varName] !== null ? $this->_secure[$varName] : $default;
    }


    /**
     * @param string $varName
     * @param mixed $value
     * @return $this
     */
    public function set($varName, $value)
    {
        if (is_array($varName) && $value === null) {
            foreach ($varName as $k => $v) {
                $this->set($k, $v);
            }
            return $this;
        }
        $_GET[$varName] = $value;
        $this->_secure[$varName] = $value;
        $this->input[$varName] = $value;
        $this->$varName = $value;
        return $this;
    }

    function gets($place = null, $secure = true, $ignoreEmpty = false)
    {

        if (is_array($place)) {
            $retval = array();
            foreach ($place as $p) {
                $x = null;
                if ($secure) {
                    $retval = $this->getSecure($p);
                } else {
                    $retval = $this->get($p);
                }
                if (empty($x) && $ignoreEmpty)
                    continue;
                $retval[$p] = $x;
            }
            return $retval;
        } elseif ($place !== null) {
            if (!isset($this->_inputbyplace[$place])) {
                return array();
            }
            if ($secure) {
                $p = $this->_inputbyplace[$place];

                $retval = array();
                if ($p) {
                    foreach ($p as $k => $v) {
                        $v = $this->getSecure($k);
                        if ($v) {
                            $retval[$k] = $v;
                        }
                    }
                }
            } else {
                $retval = $this->_inputbyplace[$place];
            }
            return $retval;
        } else {
            $retval = array();
            if ($this->input) {
                foreach ($this->input as $k => $v) {
                    if (empty($v) && $ignoreEmpty)
                        continue;
                    $retval[$k] = $v;
                }
            }
        }
        return $retval;
    }

    public function secureVar($val)
    {
        if (is_array($val) || is_object($val)) {
            $retval = is_array($val) ? array() : new \stdClass();
            foreach ($val as $k => $v) {
                $tmp = $this->secureVar($v);
                if (is_array($val)) {
                    $retval[$k] = $tmp;
                } else {
                    $retval->$k = $tmp;
                }
            }
            return $retval;
        }
        $retval = $val;
        foreach ($this->_securityFilters as $filter) {
            $instance = Security::getInstance($filter);
            if ($instance) {
                $retval = $instance->clean($retval);
            }
        }
        return htmlentities($retval);
    }


}

?>

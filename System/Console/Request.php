<?php
namespace System\Console;
if (!defined("CGAF"))
    die("Restricted Access");

class Request extends \BaseObject implements \IRequest
{
    private $_input;
    private $_tty;

    function __construct()
    {
        $arg = $_SERVER["argv"];
        array_shift($arg);
        $this->parse($arg);
    }

    /**
     * @param string $varname
     * @param mixed $default
     * @param bool $secure
     * @param null $place
     * @return mixed|null
     */
    public function get($varname, $default = null, $secure = true, $place = null)
    {
        return isset($this->_input[$varname]) ? $this->_input[$varname] : $default;
    }

    private function tovar($v)
    {
        $s = \Strings::FromLastPos($v, '-');
        return $s;
    }

    private function parse($args)
    {
        /*foreach ( $args as $v ) {
            if (strpos ( $v, "=" ) > 0) {
                $x = explode ( "=", $this->toVar ( $v ) );
                $this->_input [$x [0]] = $x [1];
            } else {
                $v = $this->toVar ( $v );
                $this->_input [$v] = true;
            }
        }*/
        $this->_input = $this->arguments($args);
    }

    function arguments($argv)
    {
        $_ARG = array();
        $matches = null;
        $match = null;
        foreach ($argv as $arg) {
            if (preg_match('#^-{1,2}([a-zA-Z0-9]*)=?(.*)$#', $arg, $matches)) {
                $key = $matches[1];
                switch ($matches[2]) {
                    case '':
                    case 'true':
                        $arg = true;
                        break;
                    case 'false':
                        $arg = false;
                        break;
                    default:
                        $arg = $matches[2];
                }
                /* make unix like -afd == -a -f -d */
                if (preg_match("/^-([a-zA-Z0-9]+)/", $matches[0], $match)) {
                    $string = $match[1];
                    for ($i = 0; strlen($string) > $i; $i++) {
                        $_ARG[$string[$i]] = true;
                    }
                } else {
                    $_ARG[$key] = $arg;
                }
            } else {
                $_ARG['input'][] = $arg;
            }
        }
        return $_ARG;
    }

    /**
     * @param string $place
     * @param bool $secure
     * @return
     */
    public function gets($place = null, $secure = true)
    {
        return $this->_input;
    }


    public function secureVar($var)
    {
        return $var;
    }
}

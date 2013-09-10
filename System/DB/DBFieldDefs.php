<?php
namespace System\DB;
class DBFieldDefs
{
    private $_vars = array();

    function __construct($arg = null)
    {

        if ($arg instanceof \SimplePHPDoc) {
            //$ori = $arg;
            $arg = $arg->getVars();
            if ($arg) {
                foreach ($arg as $k => $v) {
                    switch (strtolower($k)) {
                        case 'fieldlength' :
                            $v = ( int )$v;
                            break;
                        default :
                            ;
                            break;
                    }
                    $this->$k = $v;
                }
            }

        }
    }

    function __set($name, $value)
    {
        $name = strtolower($name);
        $this->_vars [$name] = $value;
    }

    function __get($varname)
    {
        $varname = strtolower($varname);
        switch ($varname) {
            case 'fieldtype' :
                return isset ($this->_vars ['fieldtype']) ? $this->_vars ['fieldtype'] : (isset ($this->_vars ['var']) ? $this->_vars ['var'] : null);
                break;
        }
        return isset ($this->_vars [$varname]) ? $this->_vars [$varname] : null;
    }

    function isAllowNull()
    {
        if ($this->isPrimaryKey()) {
            return false;
        }
        if (!isset ($this->_vars ['fieldallownull'])) {
            return true;
        }
        return (( string )$this->_vars ['fieldallownull'] === 'true');
    }

    function isAutoIncrement()
    {


        $extra = isset($this->_vars['fieldextra']) ? (stripos($this->_vars['fieldextra'], 'auto_increment') !== false ? true : false) : false;
        return isset($this->_vars['fieldautoincrement']) ? (bool)$this->_vars['fieldautoincrement'] : $extra;
    }

    function isPrimaryKey()
    {
        return ( bool )$this->fieldisprimarykey === true;
    }
}
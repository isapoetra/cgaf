<?php
namespace System\DB\Adapters\JSON;

use System\DB\DBException;
use System\DB\DBQuery;

class JSONTableSQL extends JSONTable
{
    private $_filter = array();
    private $_select = array();
    private $_expr = array();

    function clear($what = 'all')
    {
        $this->_filter = array();
        return $this;
    }

    function filterResult($r)
    {
        $args = func_get_args();
        $info = $this->getField($args[0]);
        if (!$info) {
            throw new DBException("Invalid field " . $args[0]);
        }
        $this->_filter [] = $args;
    }

    function addSelectExpr($expr)
    {
        $alias = str_replace('`', '', isset($expr['alias']) ? $expr['alias'] : ppd($expr));
        $this->_expr[$alias] = $expr['sub_tree'];
        $this->addSelect($alias);
    }

    function addSelect($c)
    {
        if ($c === '*') {
            $fields = array_keys($this->_getFieldDefs());
            foreach ($fields as $f) {
                $this->addSelect($f);
            }
            return $this;
        }
        $this->_select [] = trim($c);
        return $this;
    }

    private function _iseqfilt($o)
    {
        $f = $this->_filter;
        $sfilt = '';
        $idx = 0;

        foreach ($f as $ff) {
            $oper = $ff [1];
            $handle = false;
            $val = trim($ff [2], '\' ');
            $fi = $this->getFieldInfo($ff[0]);
            switch ($oper) {
                case 'like':
                    $xval = (isset($o->$ff [0]) ? $o->$ff [0] : $fi->default_value);
                    //$xval = '%com%';
                    $spos = strpos($val, '%');
                    $epos = strrpos($val, '%');
                    $cnt = \Strings::CharCount($val, '%');
                    $sval = str_replace('%', '', $val);

                    switch ($cnt) {
                        case 0: // no special char
                            break;
                        case 1:

                            if ($val[0] == '%') {
                                if (\Strings::Contains($xval, $sval)) {
                                    $sfilt .= '(true)';
                                } else {
                                    $sfilt .= '(false)';
                                }
                            } else {
                                if (substr($xval, 0, $spos) == $sval) {
                                    $sfilt .= '(true)';
                                } else {
                                    $sfilt .= '(false)';
                                }

                            }
                            break;
                        default:

                            if (\Strings::Contains($xval, $sval)) {
                                $sfilt .= '(true)';
                            } else {
                                $sfilt .= '(false)';
                            }

                    }
                    $handle = true;
                case '=' :
                    $oper = '===';
                    break;
            }

            if (!$handle) {
                $sfilt .= '(\'' . (isset($o->$ff [0]) ? $o->$ff [0] : $fi->default_value) . '\'' . $oper . '\'' . $val . '\')';
            }
            if ($idx < count($f) - 1) {
                $sfilt .= '&&';
            }
            $idx++;
        }
        //ppd($sfilt);
        eval ("\$sfilt=$sfilt;");
        return $sfilt;
    }

    function load()
    {
        $rows = parent::load();
        if (count($rows) == 0) {
            return $rows;
        }
        if ($this->_filter) {
            $retval = array();
            foreach ($rows as $row) {
                if ($this->_iseqfilt($row)) {
                    $retval [] = $row;
                }
            }
            $rows = $retval;
        }
        return $this->filtSelect($rows);
    }

    function exec($sql = null)
    {
        switch ($this->_type) {
            case DBQuery::MODE_UPDATE:
                if (!$this->_update) {
                    throw new DBException('db.json.emptyupdate');
                }
                $row = $this->loadObject();
                if (!$row) return true;
                foreach ($this->_update as $k => $v) {
                    $f = $this->getField($k);
                    if ($f->isValid($v[1])) {
                        $row->{$k} = $v[1];
                    }
                }
                $this->_putRow($row);
                return true;
            case DBQuery::MODE_DELETE:
                if (!$this->_where) {
                    $this->deleteRows();
                } else {
                    ppd($this);
                }

                break;
            default:
                throw new DBException('Unhandled command ' . $this->_type);
        }
    }

    function loadObject($o = null)
    {
        $retval = $this->load();
        return isset($retval[0]) ? $retval[0] : null;
    }

    private function _parseExprRow($expr, $row)
    {
        $nexpr = '';
        foreach ($expr as $ex) {
            switch ($ex['expr_type']) {
                case 'colref':
                    $f = explode('.', $ex['base_expr']);
                    if (isset($row->{$f[1]})) {
                        $nexpr .= '\'' . $row->{$f[1]} . '\'';
                    }
                    break;
                case 'operator':
                    switch ($ex['base_expr']) {
                        case '+':
                            $nexpr .= '.';
                            break;
                        default:
                            throw new DBException('unhandled operator expression ' . $ex['base_expr']);
                    }
                    break;
                case 'aggregate_function':
                    switch (strtolower($ex['base_expr'])) {
                        case 'count':
                            return $this->getDataConfig('numrow', 0);
                    }
                default:
                    ppd($ex);
            }
        }
        $rval = '';
        eval('$rval=' . $nexpr . ';');
        return $rval;
    }

    private function filtSelect($row)
    {
        if (!$this->_select) {
            return $row;
        }
        $retval = array();
        foreach ($row as $r) {
            $n = new \stdClass ();
            foreach ($this->_select as $v) {
                if (isset($this->_expr[$v])) {
                    $n->$v = $this->_parseExprRow($this->_expr[$v], $r);
                } else {
                    if (isset($r->$v)) {
                        $n->$v = $r->$v;
                    } else {
                        $n->$v = null;
                    }
                }
            }
            $retval [] = $n;
        }
        return $retval;
    }
}

?>
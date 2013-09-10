<?php
namespace System\DB\Adapters\JSON;

use System\DB\Adapters\JSON;
use System\DB\DBException;
use System\DB\DBResultList;

class JSONSQL
{
    /**
     * @var JSON
     */
    private $_db;
    private $_objects = array();
    private $_lastSQL;
    private $_lastId = -1;
    private static $_last;
    private static $_lastInsertId = -1;

    function __construct(JSON $db)
    {
        $this->_db = $db;
    }

    function getLastSQL()
    {
        return $this->_lastSQL;
    }

    /**
     * @param $t
     *
     * @return JSONTableSQL
     */

    private function _getTableObject($t)
    {
        $t = str_replace('`', '', $t);
        if (!$this->_db->isObjectExist($t, 'table')) {
            return null;
        }
        if (isset($this->_objects[$t])) {
            /** @noinspection PhpUndefinedMethodInspection */
            return $this->_objects[$t]->clear();
        }
        $this->_objects[$t] = new JSONTableSQL($this->_db, $t);
        return $this->_objects[$t];
    }

    private function _normalizew($tables, &$where)
    {
        if (count($tables) === 1) {
            $k = is_object($tables) ? array(
                $tables->TableName
            ) : array_keys($tables);
            $tname = is_object($tables) ? $tables->TableName : $tables[$k[0]]->TableName;
            if ($where) {
                foreach ($where as $kw => $w) {
                    switch ($w['expr_type']) {
                        case 'expression':
                            $st = $w['sub_tree'];
                            foreach ($st as $kt => $tree) {
                                switch ($tree['expr_type']) {
                                    case 'colref':
                                        $col = explode('.', $tree['base_expr']);
                                        if (!isset($col[1])) {
                                            $where[$kw]['sub_tree'][$kt]['base_expr'] = $tname . '.' . $tree['base_expr'];
                                        }
                                        break;
                                    default:
                                        break;
                                }
                            }
                            break;
                        default:
                            break;
                    }
                }
            }
        }
    }

    private function prepareSQL($sql)
    {
        $sql = str_ireplace('#__', $this->_db->getArg('prefix', ''), $sql);
        $sql = str_ireplace('`', '', $sql);
        return $sql;
    }

    private function _sortRow($rows, $field, $dir = 'asc')
    {
        $nsort = array();
        foreach ($rows as $row) {
            if (!isset($row[$field])) {
                pp($row);
                ppd($field);
            }
            $nsort[$row[$field]] = $row;
        }
        //TODO Check for type and smarter sorting
        switch ($dir) {
            case 'asc':
                asort($nsort);
                break;
            case 'desc':
                arsort($nsort);
        }
        $retval = array();
        foreach ($nsort as $v) {
            $row = array();
            foreach ($v as $vv) {
                $row[] = $vv;
            }
            $retval[] = $row;
        }
        return $retval;
    }

    private function _filterOrder($rows, $order, $fieldselect)
    {
        $retval = array();
        $nsort = array();
        foreach ($order as $v) {
            $expt = explode('.', $v['base_expr']);
            $expt[1] = trim(str_replace('asc', '', $expt[1]));
            $nsort[$expt[1]] = strtolower($v['direction']);
        }
        //reduce workaround
        $nrows = array();
        foreach ($rows as $k => $v) {
            $row = array();
            foreach ($fieldselect as $idx => $field) {
                $t = $this->_getTableObject($field[0]);
                $fi = $t->getFieldInfo($field[1]);
                $row[$fi->field_name] = $v[$idx];
            }
            $nrows[] = $row;
        }
        foreach ($nsort as $k => $v) {
            $rows = $this->_sortRow($nrows, $k, $v);
        }
        return $rows;
    }

    private function addWhere($tables, $where)
    {
        $where = $where ? $where : null;
        $this->_normalizew($tables, $where);
        if ($where) {

            foreach ($where as $w) {
                switch ($w['expr_type']) {
                    case 'operator':
                        break;
                    case 'expression':
                        $st = $w['sub_tree'];
                        if (count($st) === 3) {
                            switch ($st[0]['expr_type']) {
                                case 'colref':
                                    $col = explode('.', $st[0]['base_expr']);
                                    if (is_object($tables)) {
                                        $tables->filterResult($col[1], $st[1]['base_expr'], $st[2]['base_expr']);
                                    } else {
                                        if (!isset($tables[$col[0]])) {
                                            ppd($w);
                                        }
                                        $tables[$col[0]]->filterResult($col[1], $st[1]['base_expr'], $st[2]['base_expr']);
                                    }
                                    break;
                                case 'operator':
                                    dj($where);
                                    break;
                                default:
                                    dj($st);
                                    break;
                            }
                        }
                        break;
                    default:
                        pp($w['expr_type']);
                        ppd($w);
                        dj($w);
                        break;
                }
            }
        }
    }

    private function _execSelect($p)
    {
        $from = $p['FROM'];
        $tables = array();
        foreach ($from as $v) {
            $t = $this->_getTableObject($v['table']);
            if ($t) {
                $tables[$v['alias']] = $t;
            } else {
                throw new DBException('Table ' . $v['table'] . ' not exists');
            }
        }
        if (isset($p['WHERE'])) {
            $this->addWhere($tables, $p['WHERE']);
        }
        $select = $p['SELECT'];
        $k = array_keys($tables);
        $tname = $tables[$k[0]]->TableName;
        $fieldselect = array();
        foreach ($select as $s) {
            switch ($s['expr_type']) {
                case 'operator':
                    if ($s['base_expr'] !== '*') {
                        dj($select);
                    }
                case 'expression':
                    $tables[$tname]->addSelectExpr($s);
                    $s['alias'] = str_replace('`', '', $s['alias']);
                    if ($s['base_expr'] === '*') {
                        $fields = $tables[$tname]->getFields();
                        foreach ($fields as $val) {
                            $fieldselect[] = array(
                                $tname,
                                $val->field_name
                            );
                        }
                    } else {
                        $fieldselect[] = array(
                            $tname,
                            $s['alias']
                        );
                    }
                    break;
                case 'colref':
                    $rt = explode('.', $s['base_expr']);
                    $fs = array();
                    if (count($rt) > 1) {
                        $fs = array(
                            $rt[0],
                            $rt[1]
                        );
                        $tables[$rt[0]]->addSelect($rt[1]);
                        $rt = $rt[1];
                    } else {
                        $fs = array(
                            $tname,
                            $rt[0]
                        );
                        $tables[$tname]->addSelect($rt[0]);
                    }
                    if (isset($s['alias'])) {
                        if (strpos($s['alias'], '.') == false) {
                            $fs[2] = str_replace('`', '', $s['alias']);
                        }
                    }
                    $fieldselect[] = $fs;
                    break;
                default:
                    ppd($s);
            }
        }
        $rows = array();
        /**
         * @var JSONTableSQL $v
         */
        foreach ($tables as $v) {
            $rows[$v->getTableName()] = $v->load();
        }
        $retval = new \stdClass();
        $retval->cols = array();
        $retval->rows = array();
        $rowt = $rows[$tname];
        foreach ($fieldselect as $k => $v) {
            $retval->cols[] = isset($v[2]) ? $v[2] : $v[1];
        }
        foreach ($rowt as $row) {
            $r = array();
            foreach ($fieldselect as $k => $v) {
                $v[1] = trim($v[1]);
                if ($v[0] === $tname) {
                    if ($v[1] === '*') {
                        foreach ($row as $rk => $rv) {
                            $r[] = $rv;
                        }
                    } else {
                        if (isset($v[2])) {
                            $r[] = $row->$v[1];
                        } else {
                            $r[] = $row->$v[1];
                        }
                    }
                } else {
                    $r[] = null;
                }
            }
            $retval->rows[] = $r;
        }
        if (isset($p['ORDER'])) {
            $retval->rows = $this->_filterOrder($retval->rows, $p['ORDER'], $fieldselect);
        }
        return $retval;
    }

    public function exec($sql)
    {
        $sql = $this->prepareSQL($sql);
        $this->_lastSQL = $sql;
        $this->_lastId = -1;
        $s = new \System\DB\PHPSQLParser($sql);
        $this->_db->checkPrivs($s);
        $p = $s->parsed;
        //$retval = new \System\DB\DBResultList ();
        if (isset($p['SELECT'])) {
            return $this->_execSelect($p);
        } elseif (isset($p['INSERT'])) {
            $o = $p['INSERT'];
            $t = $this->_getTableObject($o['table']);
            if (!$t) {
                $this->_db->_throwError(2001, $o['table']);
            }
            $ids = array();
            $c = $o['cols'];
            $cols = $t->Fields;
            $tfields = array();
            foreach ($cols as $v) {
                $tfields[] = $v->field_name;
            }
            if ($c === 'ALL') {
                $c = array();
                $idx = 0;
                foreach ($o['values'] as $v) {
                    if (!isset($tfields[$idx])) {
                        break;
                    }
                    $c[$tfields[$idx]] = $v;
                    $idx++;
                }
            } else {
                $tmp = array();
                foreach ($o['values'] as $idx => $v) {
                    $tmp[$o['cols'][$idx]] = $v;
                }
                $c = $tmp;
            }
            $r = $t->insert($c);
            $this->_lastId = $t->getLastInsertId();
            return $r;
        } elseif (isset($p['DROP'])) {
            array_shift($p['DROP']);
            $q = $p['DROP'];
            $type = strtolower(array_shift($q));
            array_shift($q);
            if (count($q) == 1) {
                $o = $this->_db->getObjectInfo($q[0], $type, false);
                if ($o) {
                    if ($o->drop()) {
                        $this->_db->on('drop', $type, $q[0]);
                        return true;
                    }
                    return false;
                } else {
                    return true;
                }
            }
            pp($q);
            ppd($type);
        } elseif (isset($p['UPDATE'])) {
            $t = $this->_getTableObject($p['UPDATE'][0]['table']);
            if (!$t)
                throw new DBException('Invalid object table ' . $p['UPDATE'][0]['table']);
            if (!isset($p['SET'])) {
                throw new DBException('Invalid update statement' . (CGAF_DEBUG ? '  [' . $sql . ']' : ''));
            }
            $sets = $p['SET'];
            foreach ($sets as $set) {
                if (!$t->hasfield($set['column'])) {
                    throw new DBException('invalid update statement, unknown column ' . $set['column'] . ' on table ' . $p['UPDATE'][0]['table']);
                }
                $t->update($set['column'], $this->_db->unquote($set['expr']));
            }
            if ($p['WHERE']) {
                $this->addWhere($t, $p['WHERE']);
            }
            if ($t->exec()) {
                return $t->load();
            }
        } elseif (isset($p['DELETE'])) {
            $t = $this->_getTableObject($p['DELETE']['TABLES'][0]);
            if (!$t) {
                throw new DBException('Unable to find object table ' . $p['DELETE']['TABLES'][0]);
            }
            if (isset($p['WHERE'])) {
                $this->addWhere($t, $p['WHERE']);
            }
            $t->delete();
            $t->exec();
        } else {
            ppd($p);
            throw new DBException('unhandled sql command');
            ppd($p);
        }
    }

    private static function toResultObject($res)
    {
        $retval = new DBResultList();
        if (!is_object($res)) {
            return $res;
        }
        foreach ($res->rows as $row) {
            $srow = new \stdClass();
            foreach ($res->cols as $idx => $col) {
                $srow->$col = $row[$idx];
            }
            $retval->assign($srow);
        }
        return $retval;
    }

    public static function getLastInsertId()
    {
        return self::$_lastInsertId;
    }

    public static function getLast()
    {
        return self::$_last;
    }

    public static function getResults($db, $sql)
    {
        self::$_lastInsertId = -1;
        $i = new JSONSQL($db);
        $res = $i->exec($sql);
        self::$_last = $i->getLastSQL();
        self::$_lastInsertId = $i->_lastId;
        //ppd(self::$_lastInsertId);
        return self::toResultObject($res);
    }
}

?>
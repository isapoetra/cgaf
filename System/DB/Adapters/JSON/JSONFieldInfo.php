<?php
namespace System\DB\Adapters\JSON;

use System\DB\TableField;

class JSONFieldInfo extends TableField
{
    private function to($type, $val)
    {
        if ($val === null) {
            return null;
        }
        switch ($type) {
            case 'boolean' :
                if (!is_bool($val)) {
                    $val = ( bool )$val;
                }
                $val = ( bool )$val ? true : false;
                break;
            case 'smallint':
            case 'int':
            case 'integer':
                $val = (int)$val;
                break;
            case 'varchar':
                $val = \Convert::toString($val);
                break;
            case 'timestamp':
                switch (strtoupper($val)) {
                    case 'CURRENT_TIMESTAMP':
                        $val = \CDate::Current();
                        break;
                    default:
                        $date = new \CDate($val);
                        $val = $date->format(DATE_ISO8601);
                }
                break;
            default :
                ppd($type);
                break;
        }
        return $val;
    }

    function isValid(&$val)
    {
        $val = $this->to($this->field_type, $val);
        if (trim($val) == '' && $this->extra && $table = $this->getTable()) {
            $ex = explode(' ', $this->extra);
            foreach ($ex as $e) {
                switch (strtolower($e)) {
                    case 'auto_increment' :
                        $val = $table->getNextAutoIncrement();
                        break;
                    default :
                        ppd($e);
                        break;
                }
            }
        } elseif (trim($val) == '' && $this->auto_inc) {
            $table = $this->getTable();
            $val = $table->getNextAutoIncrement();
        }
        if ((!$this->allow_null || $this->primary) && trim($val) === '') {
            if ($this->default_value) {
                $val = $this->to($this->field_type, $this->default_value);
                return true;
            }
            return false;
        }
        if ($this->reference) {
            $q = new DBQuery ($this->_connection);
            //$valid = false;
            foreach ($this->reference as $ref) {
                switch ($ref->type) {
                    case 'table' :
                        $q->clear();
                        $q->addTable($ref->object);
                        $q->select($ref->expr);
                        $q->where($q->quoteTable($this->field_name) . '=' . $q->quote($val));
                        $o = $q->loadObject();
                        if (!$o) {
                            return false;
                        }
                        break;
                    default :
                        ppd($ref);
                }
            }
        }
        return true;
    }
}

?>
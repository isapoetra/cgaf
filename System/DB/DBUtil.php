<?php
namespace System\DB;
if (!class_exists('DB_Error', false)) {
    class DB_Error
    {

    }
}
abstract class DBUtil
{
    public static function toModule($tableName, $conn)
    {
        $tableName = str_ireplace("`", "", $tableName);
        $tableName = str_ireplace("{$conn->database}.", "", $tableName);
        if (!self::tablehasprefix($conn, $tableName)) {
            return $tableName;
        }
        $pref = $conn->TablePrefix;
        return str_ireplace($pref, '', $tableName);
    }

    public static function toTableName($conn, $tableName, $replaceprefix = '#__')
    {
        $pref = $conn->TablePrefix;
        $replacer = $conn->TablePrefix ? $conn->TablePrefix : "";
        if (strpos($tableName, ".") > 0) {
            $tableName = str_ireplace("`", "", $tableName);
            $t = explode(".", $tableName);
            // 0 is database name
            $db = DBUtil::getDatabaseByName($t [0]);
            if ($db) {
                if (!self::tablehasprefix($db, $t [1])) {
                    $t [1] = $db->TablePrefix . $t [1];
                }
                $t [1] = str_ireplace($replaceprefix, $replacer, $t [1]);
            }
            return '`' . implode("`.`", $t) . '`';
        }
        if (!self::tablehasprefix($conn, $tableName)) {
            $db = $conn->databaseName;
            $tableName = "`$db`.`" . $pref . $tableName . "`";
        } else {
            if (strpos($tableName, ".") === false) {
                $tableName = self::toTableName($conn, $conn->database . "." . $tableName);
            }
        }
        return $tableName;
    }

    public static function tablehasprefix($db, $tableName, $qprefix = '#__')
    {
        $pref = $db->TablePrefix;
        $tableName = str_ireplace("`", "", $tableName);
        if (substr($tableName, 0, strlen($db->database)) == $db->database) {
            $tableName = substr($tableName, strlen($db->database) + 1);
        }
        return (substr($tableName, 0, strlen($pref)) == $pref) || (substr($tableName, 0, strlen($qprefix)) == $qprefix);
    }

    public static function isError($obj)
    {
        return ($obj === null) || ($obj === false) || ($obj instanceof \Exception);
    }

    public static function insertObject($table, &$object, $keyName = NULL, IDBConnection $db)
    {
        $q = new DBQuery ($db);
        $q->AddTable($table);
        $fields = array();
        $values = array();
        if (self::tablehasprefix($db, $table)) {
            $fmtsql = "INSERT INTO $table ( %s ) VALUES ( %s ) ";
        } else {
            $fmtsql = "INSERT INTO `#__$table` ( %s ) VALUES ( %s ) ";
        }
        foreach (get_object_vars($object) as $k => $v) {
            if (is_array($v) or is_object($v) or $v == NULL) {
                continue;
            }
            if ($k [0] == '_') { // internal field
                continue;
            }
            $fields [] = $k;
            if ($v !== null) {
                $values [] = "'" . $q->quote(strip_tags($v)) . "'";
            } else {
                $values [] = "null";
            }
        }
        $sql = sprintf($fmtsql, implode(",", $fields), implode(",", $values));
        $ret = $q->exec($sql);
        if ($ret instanceof DB_Error) {
            \Logger::trace(__FILE__, __LINE__, __CLASS__, "failed Executing Query : $sql", 'DB');
            return false;
        }
        $iid = $q->exec();
        if ($iid instanceof DB_ERROR) {
            return $iid;
        }
        $id = $db->getLastInsertId();
        if ($keyName && $id) {
            if (is_array($object->$keyName)) {
                $object->$keyName [0] = $id;
            } else {
                $object->$keyName = $id;
            }
        }
        return $id;
    }

    public static function updateObject($table, &$object, $keyName, $updateNulls, $db, $new = null)
    {
        $q = new DBQuery ($db);
        $q->AddTable($table);
        // CGAF :: Trace(__FILE__, __LINE__, "e1", print_r($object,true),
        // false);
        if (is_array($keyName)) {
            foreach ($keyName as $vk) {
                $q->where("$vk='" . $q->quote($object->$vk) . "'");
            }
        } else {
            if ($new !== null) {
                $q->Update($keyName, $q->quote($new));
            }
            $q->where("$keyName='" . $q->quote($object->{$keyName}) . "'");
        }
        foreach (get_class_vars(get_class($object)) as $k => $v) {
            if (is_array($keyName)) {
                $iskey = false;
                foreach ($keyName as $vk) {
                    if ($k == $vk) {
                        $iskey = true;
                    }
                }
                if ($iskey) {
                    continue;
                }
            } elseif ($k == $keyName) {
                continue;
            }
            if ($object->$k === null && !$updateNulls) {
                continue;
            }
            if ($object->$k === "") {
                $val = "";
            } else {
                $val = $q->quote(strip_tags($object->$k));
            }
            $q->Update($k, $val);
        }
        $q->exec();
        return true;
    }

    static public function exec($sql, IDBConnection $conn)
    {
        return $conn->exec($sql);
    }

    static public function getFieldFormat($fieldname, $value, $tableinfo, $Out = true)
    {
        $fieldtype = "string";
        $fieldinfo = null;
        if (is_string($tableinfo)) {
            $fieldtype = $tableinfo;
        } elseif (is_array($tableinfo)) {
            $fieldlist = $tableinfo ["Fields"];
            foreach ($fieldlist as $v) {
                if ($v ["name"] == $fieldname) {
                    $fieldinfo = $v;
                    $fieldtype = $fieldinfo ["type"];
                    break;
                }
            }
        }
        $retval = $value;
        // CGAF::$Message->Add("$retval $fieldtype");
        switch ($fieldtype) {
            case "blob" :
            case "time" :
            case "string" :
                break;
            case "int" :
            case "real" :
                if ($Out) {
                    $retval = number_format($retval, 0, '.', ',');
                }
                break;
            case "timestamp" :
            case "datetime" :
                if (empty ($value)) {
                    return $value;
                }
                $retval = $value;
                if (preg_match("/\d{2}\/\d{2}\/\d{4} \d{2}:\d{2}:\d{2}/", $retval)) {
                    $regs = array();
                    ereg("([0-9]{2})/([0-9]{2})/([0-9]{4}) ([0-9]{2})\:([0-9]{2})\:([0-9]{2})", $retval, $regs);
                    // $retval= $regs[3]."-".$regs[1]."-".$regs[2].
                    // ($fieldinfo["type"] === "date" ? " 00:00:00" : "");
                    $retval = $regs [3] . "-" . $regs [2] . "-" . $regs [1] . " " . $regs [4] . ":" . $regs [5] . ":" . $regs [6];
                } elseif (is_int($retval)) {
                    $retval = date('Y-m-d H:i:s', $retval);
                }
                //
                // return ;
                // $retval = Date('Y-m-d H:i:s',$retval);
                $tretval = new \CDate ($retval);
                if ($Out) {
                    $retval = $tretval->format("%d/%m/%Y %H:%M:%S");
                } else {
                    $retval = $tretval->format("%Y-%m-%d %H:%M:%S");
                }
                // CGAF::$Message->Add($value . ".. " .$retval);
                break;
            case "date" :
                if (!$value) {
                    return $value;
                }
                if (preg_match("/\d{2}\/\d{2}\/\d{4}/", $retval)) {
                    $regs = array();
                    ereg("([0-9]{2})/([0-9]{2})/([0-9]{4})", $retval, $regs);
                    $retval = $regs [3] . "-" . $regs [2] . "-" . $regs [1];
                } elseif (is_int($retval)) {
                    $retval = Date('Y-m-d', $retval);
                }
                $tretval = new \CDate ($retval);
                if ($Out) {
                    $retval = $tretval->format("%d/%m/%Y");
                } else {
                    $retval = $tretval->format("%Y-%m-%d");
                }
                break;
            default :
               \Logger::debug(__CLASS__ . '@' . __LINE__ . " Unknown Format " . $fieldtype);
                break;
        }
        // CGAF :: $Message->Add("Format : ".$fieldtype." $retval");
        return $retval;
    }

    public static function dumpTable($conn, $tablename, $includeData = false, $dropexisting = false)
    {
        // TODO: sql based on database
        $q = new DBQuery ($conn);
        if ($dropexisting) {
            $q->addSQL("select 'drop table if exists `$tablename`' stmt");
        }
        $q->addSQL("show create table $tablename");
        // $q->addSQL("show create table $tablename");
        $res = $q->exec();
        $retval = array();
        foreach ($res as $ret) {
            $r = $ret [0];
            if (isset ($r->{"Create Table"})) {
                $retval [] = $r->{"Create Table"};
            } elseif (isset ($r->stmt)) {
                $retval [] = $r->stmt;
            }
        }
        $str = "";
        foreach ($retval as $x) {
            $str .= $x . ";\n";
        }
        $str .= "commit;\n\n";
        return $str;
    }

    public static function dumptabledata($conn, $tableName, $commitRow = 0)
    {
        $q = new DBQuery ($conn);
        $q->addTable($tableName);
        $cur = $q->exec();
        $c = 0;
        $str = "";
        while ($row = $cur->next()) {
            $str .= "insert into `$tableName` values(";
            foreach ($row as $data) {
                if ($data == null) {
                    $str .= "null,";
                } else {
                    $str .= $q->quote($data) . ",";
                }
            }
            $str = substr($str, 0, strlen($str) - 1);
            $str .= ");\n";
            if ($commitRow > 0 && $c == $commitRow) {
                $str .= "commit;\n";
                $c = 0;
            }
        }
        if ($str != "") {
            $str .= "commit;\n\n";
        }
        return $str;
    }

    public static function loadHashList($sql, $conn)
    {
        $q = new DBQuery ($conn);
        $retval = $q->exec($sql);
        if ($retval) {
            return $retval->toHashList();
        }
        return null;
    }

    public static function getPKFromTable($tableName, IDBConnection $conn, $asString = false)
    {
        $tinfo = $conn->getObjectInfo($tableName);
        $retval = array();
        foreach ($tinfo as $info) {
            if ($info->primary) {
                $retval [] = $info->field_name;
            }
        }
        return $asString ? implode(',', $retval) : $retval;
    }

    public static function execScript($file, $conn, $replacer = array())
    {
        if (is_file($file)) {
            $q = new DBQuery ($conn);
            $q->loadSQLFile($file);
            return $q->exec();
        }
        return false;
    }

    public static function getPK($table, $row, DBConnection $conn)
    {
        $info = self::getPKFromTable($table, $conn);
        $retval = array();
        foreach ($row as $k => $v) {
            if (in_array($k, $info)) {
                $retval [] = $v;
            }
        }
        return implode(',', $retval);
    }

    public static function dumpRows(IDBConnection $db, $table, $rows, $fname)
    {
        if ($rows === null) {
            $rows = $db->exec('select * from ' . $db->quoteTable($table, true));
        }
        $s = array();
        while ($row = $rows->next()) {
            $r = array();
            foreach ($row as $v) {
                if ($v == null) {
                    $r[] = 'null';
                } else {
                    $r[] = $db->quote($v);
                }
            }
            $s[] = 'insert into ' . $db->quoteTable($table) . ' values(' . implode(',', $r) . ')';
        }
        if ($fname) {
            file_put_contents($fname, implode(';' . PHP_EOL, $s) . ';' . PHP_EOL);
        }
        return $s;
    }

    public static function dumpCGAFAppDB($appId, $fname)
    {
        $cgtables = array(
            'applications',
            'roles',
            'role_privs',
            'user_roles',
            'modules',
            'menus',
            'syskeys',
            'contents',
            'lookup',
            'news',
            'comment',
            'todo',
            'vote',
            'recentlog'
        );
        $cg = array();
        $s = array();
        $cdb = \CGAF::getDBConnection();
        foreach ($cgtables as $k => $v) {
            //$s[] = '---------------------'.$v.'-----------------------';
            $s[] = 'delete from #__' . $v . ' where app_id=' . $cdb->quote($appId);
            $rows = $cdb->exec('select * from ' . $cdb->quoteTable($v) . ' where app_id=' . $cdb->quote($appId));
            $s = array_merge($s, self::dumpRows($cdb, $v, $rows, null));
            //$s[] = '---------------------'.$v.'-----------------------';
        }
        file_put_contents($fname, implode(';' . PHP_EOL, $s) . ';' . PHP_EOL);
        //sysvals,vote_items
    }
}

?>
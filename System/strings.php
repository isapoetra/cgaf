<?php
class Strings extends stdClass
{
    public static function SubString($text, $start, $end = null, $more = " ...")
    {
        $end = $end ? $end : strlen($text);
        if (strlen($text) < $end - $start)
            return $text;
        return substr($text, $start, $end) . $more;
    }

    /**
     *
     * Enter description here ...
     * @param string $str
     * @param string $o
     * @param boolean $caseSensitive
     */
    public static function EndWith($str, $o, $caseSensitive = false)
    {
        if (is_array($o)) {
            foreach ($o as $v) {
                if (self::EndWith($str, $v, $caseSensitive)) {
                    return true;
                }
            }
            return false;
        }
        if (strlen($o) > strlen($str))
            return false;
        if (!$caseSensitive) {
            $str = strtolower($str);
            $o = strtolower($o);
        }
        $f = substr($str, strlen($str) - strlen($o));
        return $f === $o;
    }

    public static function BeginWith($str, $o, $caseSensitive = false)
    {
        if (is_array($o)) {
            foreach ($o as $s) {
                if (self::BeginWith($str, $s, $caseSensitive)) {
                    return true;
                }
            }
            return false;
        }
        if (strlen($o) > strlen($str))
            return false;
        if (!$caseSensitive) {
            $str = strtolower($str);
            $o = strtolower($o);
        }
        $f = substr($str, 0, strlen($o));
        return $f === $o;
    }

    public static function FromPos($str, $needle)
    {
        $needle = strpos($str, $needle) + 1;
        return substr($str, $needle);
    }

    public static function FromLastPos($str, $needle, $offset = null, $last = true)
    {
        if ($str === null) {
            return $str;
        }
        if (!is_string($str)) {
            echo '<pre>';
            debug_print_backtrace();
            die($str);
        }
        $pos = (strripos($str, $needle, $offset) > 0) ? strripos($str, $needle, $offset) + 1 : 0;
        return $last ? substr($str, $pos) : (self::EndWith(substr($str, 0, $pos), $needle) ? substr($str, 0, $pos - 1) : substr($str, 0, $pos));
    }

    public static function Replace($search, $replace, $subject = null, $case = true, $count = null, $prefix = '', $suffix = '', $encode = false)
    {
        $retval = $subject;
        if ($replace === null && is_array($search)) {
            $retval = $subject;
            foreach ($search as $k => $v) {
                $retval = self::Replace($k, $v, $retval, $case, $count, $prefix, $suffix, $encode);
            }
        } elseif (is_array($search) || is_object($search)) {
            $retval = $subject;
            foreach ($search as $k => $v) {
                $retval = self::Replace($v, $replace, $retval, $case, $count, $prefix, $suffix, $encode);
            }
        } elseif (is_array($replace) || is_object($replace)) {
            $retval = $search;

            foreach ($replace as $k => $v) {
                $retval = self::Replace($k, $v, $retval, $case, $count, $prefix, $suffix, $encode);
            }
        } elseif (is_string($replace)) {
            if ($encode) {
                $replace = urlencode($replace);
            }
            if ($case) {
                $retval = str_replace($prefix . $search . $suffix, $replace, $subject, $count);
            } else {
                $retval = str_ireplace($prefix . $search . $suffix, $replace, $subject, $count);
            }
        }
        return $retval;
    }

    public static function RemoveString($str, $search)
    {
        return self::replace("", $search, $str);
    }

    public static function Repeat($string, $cnt)
    {
        return str_repeat($string, $cnt);
    }

    public static function Contains($haystack, $needle, $offset = null, $case = false)
    {
        if (is_array($needle)) {
            foreach ($needle as $v) {
                if (self::contains($haystack, $v)) {
                    return true;
                }
            }
            return false;
        }
        if (!$needle) return true;
        if ($case) {
            return stripos($haystack, $needle, $offset) !== false;
        } else {
            return strpos($haystack, $needle, $offset) !== false;
        }
    }

    public static function isEmpty($s)
    {
        return $s === null || trim($s) === '';
    }

    public static function wordstoupper($s)
    {
        $retval = '';
        $first = true;
        for ($i = 0; $i < strlen($s); $i++) {
            if ($first || $s[$i] === '.') {
                $retval .= $s[$i];
                if (!$first) {
                    $retval .= strtoupper($s[$i + 1]);
                } else {
                    $retval .= $s[$i + 1];
                }
                $i++;
            } else {
                $retval .= strtolower($s[$i]);
            }
            $first = false;
        }
        return $retval;
    }

    public static function unHTML($s)
    {
        return self::Replace(array(
            '%20' => ' '
        ), null, $s);
    }

    public static function CharCount($haystack, $needle)
    {
        if ($needle == null) return 0;
        $count = 0;
        for ($i = 0; $i < strlen($haystack); $i++) {
            if ($haystack[$i] === $needle) $count++;
        }
        return $count;
    }
}

?>
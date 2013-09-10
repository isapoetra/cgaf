<?php
//TODO Optimize this
define('E_DEBUG', 1);

final class Logger
{
    private static $_logdata = array();
    private static $_firsttime = true;
    private static $_callbacks = array();

    public static function info()
    {
        return self::write(self::format(func_get_args()), E_NOTICE);
    }

    public static function debug()
    {
        if (!CGAF_DEBUG)
            return;
        return self::write(self::format(func_get_args()), E_DEBUG, false);
    }

    public static function Warning()
    {
        return self::write(self::format(func_get_args()), E_WARNING);
    }

    public static function getLogData()
    {
        return self::$_logdata;
    }

    private static function format($args)
    {
        if (is_array($args) && count($args) > 1) {
            $s = array_shift($args);
            if ($s === '' || $s == null) {
                $s = Strings::repeat('%s', count($args));
            }
            return vsprintf($s, $args);
        } elseif (is_array($args) && count($args) == 1) {
            return $args[0];
        } else {
            return $args;
        }
    }

    public static function WriteDebug()
    {
        return CGAF_DEBUG ? self::format(func_get_args()) : "";
    }

    public static function Error()
    {
        global $args;
        $args = func_get_args();
        self::write(self::format($args), -1);
        if (class_exists("Response", false)) {
            Response::StartBuffer();
        }
        /*if (CGAF_DEBUG && function_exists('xdebug_get_function_stack')) {
         echo '<pre>';
        debug_print_backtrace();
        var_dump(xdebug_get_function_stack());
        echo '</pre>';
        } else {*/
        $cf = CGAF::getInternalStorage('contents/') . "errors/exception.php";
        if (is_file($cf) && \System::isWebContext()) {
            include $cf;
        }
        if (class_exists("Response", false)) {
            Response::EndBuffer(true);
        }
        // }
        CGAF::doExit();
        exit(0);
    }

    /**
     * @static
     * @param \Exception $args
     * @param bool $argtag
     * @return string
     */

    private static function printArgs($args, $argtag = true)
    {
        if (function_exists('xdebug_get_function_stack')) {
            echo '<pre>';
            var_dump(xdebug_get_function_stack());
            echo '</pre>';
            exit(0);
        }
        $retval = $argtag ? '<div class="args"><span>Args  :</span>' : '';
        if (is_object($args)) {
            if ($args instanceof Exception) {
                $retval .= get_class($args) . "\n";
                $retval .= 'Message : ' . $args->getMessage() . "\n";
                $retval .= 'Trace	: ' . "\n";
                $retval .= "{\n";
                $trace = $args->getTrace();
                foreach ($trace as $v) {
                    if (isset($v['file']) && isset($v['line'])) {
                        $retval .= "\nFile : " . $v['file'] . " Line "
                            . $v['line'] . " \n";
                    }
                    if (isset($v["class"])) {
                        $retval .= 'Function : ' . $v['class'] . '->'
                            . $v['function'] . "\n"
                            . '<div onclick="this.childNodes[0].currentStyle.display=\'none\'">Args : <span style="display:block">'
                            . self::printArgs($v['args'], false)
                            . '</span></div>';
                    }
                }
                $retval .= print_r($trace, true);
                $retval .= "}\n";
                return $retval;
            } else {
                $retval .= print_r($args, true);
            }
        } else {
            $retval .= print_r($args, true);
        }
        $retval .= $argtag ? '</div>' : '';
        return $retval;
    }

    private static function print_backtrace($bt, $showargs = true)
    {
        /*if (function_exists('xdebug_var_dump')) {
         return '<pre>'. xdebug_var_dump($bt,true).'</pre>';
        }*/
        $r = "";
        $idx = 1;
        foreach ($bt as $b) {
            $r .= '<div class="row">';
            //$r .= isset($b['file']) ? "" : "<span>#$idx</span>";
            //$r .= ! isset($b['file']) ? "" : "<span>#$idx</span>";
            $msg = (isset($b['class']) ? $b['class'] : '')
                . (isset($b['type']) ? $b['type'] : '') . $b['function'];
            $f = (isset($b['file']) ? '<span class="file">@'
                    . (CGAF_DEBUG ? @$b['file']
                        : str_replace(CGAF_PATH, "", @$b['file']))
                    : "&nbsp;")
                . (isset($b['line']) ? ':' . $b['line'] . '</span>' : '');
            if ($showargs && isset($b['args']) && count($b['args'])) {
                $msg .= "(";
                foreach ($b['args'] as $arg) {
                    $msg .= '<a href="#">' . gettype($arg) . '</a>,';
                }
                $msg = substr($msg, 0, strlen($msg) - 1);
                $msg .= ')';
                $aidx = 0;
                $msg .= '<div class="args"><span>Args</span>';
                $msg .= "<ul>";
                foreach ($b['args'] as $arg) {
                    $msg .= "<li> <pre>" . self::printArgs($arg)
                        . "</pre></li>";
                    $aidx++;
                }
                $msg .= "</ul>";
                $msg .= "</div>";
            } else {
                $msg .= '()' . $f;
            }
            $r .= '<span class="class">' . $msg . '</span>';
            $r .= '</div>';
            //
            $idx++;
        }
        return $r;
    }

    public static function onLog($callback)
    {
        self::$_callbacks['onlog'][] = $callback;
    }

    private static function trigger($event, /** @noinspection PhpUnusedParameterInspection */
                                    $args = null)
    {
        $args = func_get_args();
        array_shift($args);
        $event = strtolower($event);
        $trigger = isset(self::$_callbacks[$event]) ? self::$_callbacks[$event]
            : array();
        foreach ($trigger as $v) {
            call_user_func_array($v, $args);
        }
    }

    public static function trace($file, $line, $level, $message, $group)
    {
        //__FILE__, __LINE__, E_NOTICE, "--------------Projects List ----------\n", "DB"
        self::write(
            implode('::',
                array(
                    $group,
                    $file,
                    $line,
                    $message
                )), $level);
    }

    private static function error2string($value)
    {
        if (is_string($value) && !is_numeric($value)) {
            return $value;
        }
        $level_names = array(
            E_ERROR => 'E_ERROR',
            E_WARNING => 'E_WARNING',
            E_PARSE => 'E_PARSE',
            E_NOTICE => 'E_NOTICE',
            E_CORE_ERROR => 'E_CORE_ERROR',
            E_CORE_WARNING => 'E_CORE_WARNING',
            E_COMPILE_ERROR => 'E_COMPILE_ERROR',
            E_COMPILE_WARNING => 'E_COMPILE_WARNING',
            E_USER_ERROR => 'E_USER_ERROR',
            E_USER_WARNING => 'E_USER_WARNING',
            E_USER_NOTICE => 'E_USER_NOTICE',
            E_DEBUG => 'E_USER_DEBUG'
        );
        if (defined('E_STRICT'))
            $level_names[E_STRICT] = 'E_STRICT';
        $levels = array();
        if (($value & E_ALL) == E_ALL) {
            $levels[] = 'E_ALL';
            $value &= ~E_ALL;
        }
        foreach ($level_names as $level => $name)
            if (($value & $level) == $level)
                $levels[] = $name;
        return count($levels) ? implode(' | ', $levels) : $value;
    }

    private static function string2error($string)
    {
        $level_names = array(
            'E_ERROR',
            'E_WARNING',
            'E_PARSE',
            'E_NOTICE',
            'E_CORE_ERROR',
            'E_CORE_WARNING',
            'E_COMPILE_ERROR',
            'E_COMPILE_WARNING',
            'E_USER_ERROR',
            'E_USER_WARNING',
            'E_USER_NOTICE',
            'E_ALL'
        );
        if (defined('E_STRICT'))
            $level_names[] = 'E_STRICT';
        $value = 0;
        $levels = explode('|', $string);
        foreach ($levels as $level) {
            $level = trim($level);
            if (defined($level))
                $value |= (int)constant($level);
        }
        return $value;
    }

    public static function write($s, $level = E_NOTICE, $die = null)
    {
        if (System::isConsole() && CGAF_DEBUG) {
            if (class_exists('Response', false)) {
                Response::writeln($s);
            } else {
                echo $s . "\n";
            }
            return true;
        }
        if (CGAF::isDebugMode()) {
            self::trigger('onLog', $s, $level);
        }
        $logPath = CGAF::getConfig('errors.error_log',
            \CGAF::getInternalStorage('log', false, true, 0770) . DS);
        $logFile = $logPath . strtolower(self::error2string($level)) . '.log';
        $msg = time() . '#' . \System::getRemoteAddress() . '#' . $s . '#'
            . (isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '');
        $replevel = error_reporting();
        if (($level & $replevel) != $level) {
            return true;
        }
        $f = @fopen($logFile, 'a');
        if ($f === false) {
            return 0;
        }
        fwrite($f, $msg . "\n");
        if (\CGAF::isDebugMode()) {
            $bt = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10);
            array_shift($bt);
            foreach ($bt as $b) {

                fwrite($f,
                    "\t" . @$b['file'] . '::' . @$b['line'] . '-->'
                            . @$b['function'] . ':' . @$b['class'] . "\n");
            }
        }
        fclose($f);
        $die = $level > 0 && $die !== null ? $die
            : $level == E_ERROR || $level == E_USER_ERROR
            || $level == E_CORE_ERROR
            || $level === E_RECOVERABLE_ERROR;
        if ($die) {
            echo $msg;
            if (CGAF_DEBUG) {
                echo self::print_backtrace(debug_backtrace());
            }
            CGAF::doExit();
        }
        return true;
    }

    private static function getTag($level)
    {
        switch ($level) {
            case E_ERROR:
            case E_CORE_ERROR:
                $attr = array(
                    "__tag" => "span",
                    "class" => "error"
                );
                break;
            case E_NOTICE:
                $attr = array(
                    "__tag" => "span",
                    "class" => "notice"
                );
                break;
            case E_WARNING:
            case E_USER_WARNING:
                $attr = array(
                    "__tag" => "span",
                    "class" => "warning"
                );
                break;
            default:
                $attr = array(
                    "__tag" => "span",
                    "class" => ""
                );
                break;
        }
        return $attr;
    }

    public static function Flush($direct = false)
    {
        if (!count(self::$_logdata)) {
            return false;
        }
        Utils::makeDir(CGAF_PATH . "/protected/log/");
        $f = @fopen(CGAF_PATH . "/protected/cgaf.log", 'a');
        if ($f === false) {
            return 0;
        }
        fwrite($f,
            (isset($_SERVER["REMOTE_ADDR"]) ? $_SERVER["REMOTE_ADDR"] : '')
            . "\n--------------------------\n");
        foreach (self::$_logdata as $level => $data) {
            $attr = self::getTag($level);
            foreach ($data as $s) {
                fwrite($f,
                    "<" . $attr["__tag"] . " class=\"" . $attr["class"]
                    . "\">$s</" . $attr["__tag"] . ">");
                if ((error_reporting() & $level) == $level) {
                    if (substr($s, 0, strlen($s)) == "\n") {
                        $s = substr($s, 0, strlen($s) - 1);
                    }
                    fwrite($f, $level . ",$s\n");
                }
            }
        }
        fclose($f);
        $ori = self::$_logdata;
        self::$_logdata = array();
        return $ori;
    }
}

?>

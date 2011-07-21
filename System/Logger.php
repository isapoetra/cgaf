<?php
defined ( "CGAF" ) or die ( "Restricted Access" );

//TODO Optimize this
final class Logger {
	private static $_logdata = array ();
	private static $_firsttime = true;
	private static $_callbacks = array ();

	public static function info() {
		return self::write ( self::format ( func_get_args () ), E_NOTICE );
	}

	public static function Warning() {
		return self::write ( self::format ( func_get_args () ), E_USER_WARNING );
	}
	public static function getLogData() {
		return self::$_logdata;
	}
	private static function format($args) {
		if (is_array ( $args ) && count ( $args ) > 1) {
			$s = array_shift ( $args );
			if ($s === '' || $s == null) {
				$s = String::repeat ( '%s', count ( $args ) );
			
			}
			return vsprintf ( $s, $args );
		} elseif (is_array ( $args ) && count ( $args ) == 1) {
			return $args [0];
		} else {
			return $args;
		}
	}

	public static function WriteDebug() {
		return CGAF_DEBUG ? self::format ( func_get_args () ) : "";
	}

	public static function Error() {
		global $args;
		$args = func_get_args ();
		self::write(self::format($args),-1);
		if (class_exists ( "Response", false )) {
			Response::StartBuffer ();
		}
		
		if (CGAF_DEBUG && function_exists ( 'xdebug_get_function_stack' )) {
			echo '<pre>';
			debug_print_backtrace ();
			var_dump ( xdebug_get_function_stack () );
			echo '</pre>';
		} else {
			
			include dirname ( __FILE__ ) . DS . "error/exception.php";
			if (class_exists ( "Response", false )) {
				Response::EndBuffer ( true );
			}
		}
		CGAF::doExit();
		exit ( 0 );
	}

	private static function printArgs($args, $argtag = true) {
		
		if (function_exists ( 'xdebug_get_function_stack' )) {
			echo '<pre>';
			var_dump ( xdebug_get_function_stack () );
			echo '</pre>';
			exit ( 0 );
		}
		$retval = $argtag ? '<div class="args"><span>Args  :</span>' : '';
		
		if (is_object ( $args )) {
			if ($args instanceof Exception) {
				$retval .= get_class ( $args ) . "\n";
				$retval .= 'Message : ' . $args->getMessage () . "\n";
				$retval .= 'Trace	: ' . "\n";
				$retval .= "{\n";
				$trace = $args->getTrace ();
				foreach ( $trace as $v ) {
					if (isset ( $v ['file'] ) && isset ( $v ['line'] )) {
						$retval .= "\nFile : " . $v ['file'] . " Line " . $v ['line'] . " \n";
					}
					if (isset ( $v ["class"] )) {
						$retval .= 'Function : ' . $v ['class'] . '->' . $v ['function'] . "\n" . '<div onclick="this.childNodes[0].currentStyle.display=\'none\'">Args : <span style="display:block">' . self::printArgs ( $v ['args'], false ) . '</span></div>';
					}
				}
				$retval .= print_r ( $trace, true );
				$retval .= "}\n";
				return $retval;
			} else {
				$retval .= print_r ( $args, true );
			}
		} else {
			$retval .= print_r ( $args, true );
		}
		$retval .= $argtag ? '</div>' : '';
		return $retval;
	}

	private static function print_backtrace($bt, $showargs = true) {
		if (function_exists ( 'xdebug_var_dump' )) {
			return xdebug_var_dump ( $bt );
		}
		$r = "";
		$idx = 1;
		foreach ( $bt as $b ) {
			$r .= '<div class="row">';
			//$r .= isset($b['file']) ? "" : "<span>#$idx</span>";
			//$r .= ! isset($b['file']) ? "" : "<span>#$idx</span>";
			$msg = (isset ( $b ['class'] ) ? $b ['class'] : '') . (isset ( $b ['type'] ) ? $b ['type'] : '') . $b ['function'];
			$f = (isset ( $b ['file'] ) ? '<span class="file">@' . (CGAF_DEBUG ? @$b ['file'] : str_replace ( CGAF_PATH, "", @$b ['file'] )) : "&nbsp;") . (isset ( $b ['line'] ) ? ':' . $b ['line'] . '</span>' : '');
			
			if ($showargs && isset($b ['args']) && count ( $b ['args'] )) {
				$msg .= "(";
				foreach ( $b ['args'] as $arg ) {
					$msg .= '<a href="#">' . gettype ( $arg ) . '</a>,';
				}
				$msg = substr ( $msg, 0, strlen ( $msg ) - 1 );
				$msg .= ')';
				$aidx = 0;
				$msg .= '<div class="args"><span>Args</span>';
				$msg .= "<ul>";
				foreach ( $b ['args'] as $arg ) {
					$msg .= "<li> <pre>" . self::printArgs ( $arg ) . "</pre></li>";
					$aidx ++;
				}
				$msg .= "</ul>";
				$msg .= "</div>";
			} else {
				$msg .= '()' . $f;
			}
			$r .= '<span class="class">' . $msg . '</span>';
			$r .= '</div>';
			//
			$idx ++;
		}
		return $r;
	}

	public static function onLog($callback) {
		self::$_callbacks ['onlog'] [] = $callback;
	}

	private static function trigger($event, $args = null) {
		$args = func_get_args ();
		array_shift ( $args );
		$event = strtolower ( $event );
		$trigger = isset ( self::$_callbacks [$event] ) ? self::$_callbacks [$event] : array ();
		foreach ( $trigger as $v ) {
			call_user_func_array ( $v, $args );
		}
	
	}

	public static function trace($file, $line, $level, $message, $group) {
		//__FILE__, __LINE__, E_NOTICE, "--------------Projects List ----------\n", "DB"
		self::write ( implode ( '::', array (
				$group, 
				$file, 
				$line, 
				$message ) ), $level );
	}

	public static function write($s, $level = E_NOTICE, $die = null) {
		static $logFile;
		if (!$logFile) {
			$logFile = CGAF::getConfig('errors.error_log');
		} 
		$levels = array (
				E_NOTICE => 'Notice', 
				E_USER_WARNING => 'Warning', 
				E_WARNING => 'Warning', 
				E_COMPILE_ERROR => 'Compile Error' );
		
		if (System::isConsole () && CGAF_DEBUG) {
			if (class_exists ( 'Response', false )) {
				Response::writeln ( $s );
			} else {
				echo $s . "\n";
			}
			return;
		}
		if (CGAF::isDebugMode()) {
			self::trigger ( 'onLog', $s, $level );
			self::$_logdata[] = array("level"=>$level,"message"=>$s);
		}
		
		$msg = (isset ( $levels [$level] ) ? $levels [$level] : $level) . ':' . $s;
		
		
		if ($level ===-1 ||  (error_reporting () & $level) == $level) {					
			$f = @fopen ( $logFile, 'a' );
			if ($f === false) {
				return 0;
			}
			fwrite ( $f, $msg . "\n" );
			fclose ( $f );
			$die = $level>0 && $die !== null ? $die : $level == E_ERROR || $level == E_USER_ERROR || $level == E_CORE_ERROR || $level === E_RECOVERABLE_ERROR;
			if ($die) {
				
				echo $msg;
				if (CGAF_DEBUG) {
					echo self::print_backtrace ( debug_backtrace () );
				}
				CGAF::doExit ();
			}
			return;
		}		
	}

	private static function getTag($level) {
		switch ($level) {
			case E_ERROR :
			case E_CORE_ERROR :
				$attr = array (
						"__tag" => "span", 
						"class" => "error" );
				break;
			case E_NOTICE :
				$attr = array (
						"__tag" => "span", 
						"class" => "notice" );
				break;
			case E_WARNING :
			case E_USER_WARNING :
				$attr = array (
						"__tag" => "span", 
						"class" => "warning" );
				break;
			default :
				$attr = array (
						"__tag" => "span", 
						"class" => "" );
				break;
		}
		return $attr;
	}

	public static function Flush($direct = false) {
		if (! count ( self::$_logdata )) {
			return;
		}
		Utils::makeDir ( CGAF_PATH . "/log/" );
		$f = @fopen ( CGAF_PATH . "/log/cgaf.log", 'a' );
		if ($f === false) {
			return 0;
		}
		fwrite ( $f, (isset ( $_SERVER ["REMOTE_ADDR"] ) ? $_SERVER ["REMOTE_ADDR"] : '') . "\n--------------------------\n" );
		
		foreach ( self::$_logdata as $level => $data ) {
			$attr = self::getTag ( $level );
			foreach ( $data as $s ) {
				
				fwrite ( $f, "<" . $attr ["__tag"] . " class=\"" . $attr ["class"] . "\">$s</" . $attr ["__tag"] . ">" );
				
				if ((error_reporting () & $level) == $level) {
					if (substr ( $s, 0, strlen ( $s ) ) == "\n") {
						$s = substr ( $s, 0, strlen ( $s ) - 1 );
					}
					fwrite ( $f, $level . ",$s\n" );
				}
			}
		}
		fclose ( $f );
		$ori = self::$_logdata;
		self::$_logdata = array ();
		
		return $ori;
	}
}
?>
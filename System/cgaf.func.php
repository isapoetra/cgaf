<?php
function ifnull($v,$alt) {
	return $v === null ? $alt : $v;
}

function using($namespace) {
	return \CGAF::Using ( $namespace );
}


function pp($o, $return = false) {


	if (class_exists('System',false) ) {
		if ($o === null) {
			if (System::isConsole ()) {
				$r = 'NULL';
			} else {
				$r = "<pre>NULL</pre>";
			}
		}else{
			if (! System::isConsole ()) {
				$r = "<pre>" . print_r ( $o, true ) . "</pre>";
			} else {
				$r = print_r ( $o, true );
			}
		}
	}else{
		$r =  print_r ( $o, true );
	}

	if (! $return) {
		if (class_exists ( 'Response', false )) {
			Response::write ( $r );
		} else {
			echo $r;
		}
	}
	if (class_exists('System',false)) {
		if (System::isConsole ()) {
			$r .= "\n";
		}
	}
	return $r;
}

function ppd($o, $clear = false) {
	if (class_exists('System',false)) {
		if (! System::isConsole ()) {
			header ( 'Content-Type: text/html' );
		}
	}
	echo "<pre>";
	var_dump ( $o );
	debug_print_backtrace ();
	echo "</pre>";
	if (class_exists ( "Response", false ) && ! CGAF::isShutdown ()) {
		if ($clear) {
			Response::clearBuffer ();
		}
		Response::write ( pp ( $o, true ) );
		Response::Flush ( true );
	} else {
		echo "<pre>";
		var_dump ( $o );
		debug_print_backtrace ();
		echo "</pre>";
	}
	CGAF::doExit ();
}

function ppbt() {
	echo '<pre>';
	debug_print_backtrace ();
	echo '</pre>';
}

/**
 *
 * @param String $s
 */
function __($s, $def = null) {
	return CGAF::_ ( $s, $def );
}

function ___($title, $args) {
	$args = func_get_args ();
	array_shift ( $args );
	return vsprintf ( __ ( $title ), $args );
}

function CGAFDebugOnly() {
	if (! CGAF_DEBUG) {
		throw new SystemException ( 'DEBUG ONLY' );
	}
}
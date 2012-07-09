<?php
function ifnull($v, $alt) {
	return $v === null ? $alt : $v;
}
function using($namespace) {
	return \CGAF::Using($namespace);
}
function pp($o, $return = false) {
	if (class_exists('System', false)) {
		if ($o === null) {
			if (System::isConsole()) {
				$r = 'NULL';
			} else {
				$r = "<pre>NULL</pre>";
			}
		} else {
			if (!System::isConsole()) {
				$r = "<pre>" . print_r($o, true) . "</pre>";
			} else {
				$r = print_r($o, true).PHP_EOL;
			}
		}
	} else {
		$r = print_r($o, true);
	}
	if (!$return) {
		if (class_exists('Response', false)) {
			Response::write($r);
		} else {
			echo $r;
		}
	}
	if (class_exists('System', false)) {
		if (System::isConsole()) {
			$r .= "\n";
		}
	}
	return $r;
}
function ppd($o, $clear = false) {
	if (class_exists('System', false)) {
		if (!System::isConsole()) {
			header('Content-Type: text/html; charset=UTF-8');
		}
	}
	if (class_exists("Response", false) && !CGAF::isShutdown()) {
		if ($clear) {
			Response::clearBuffer();
		}
		Response::write(pp($o, true));
		Response::Flush(true);
	}
	echo "<pre>";
	var_dump($o);
	debug_print_backtrace();
	echo "</pre>";
	CGAF::doExit();
}
function dj($arg) {
	ob_clean();
	$d = array();
	$d['args'] =  func_get_args();
	$t =debug_backtrace();
	//array_shift($t);
	$d['trace'] =  $t;
	echo json_encode($d);
	exit();
}
function ppbt() {
	echo '<pre>';
	debug_print_backtrace();
	echo '</pre>';
}
/**
 *
 * @param String $s
 */
function __($s, $def = null, $locale = null) {
	return CGAF::_($s, $def, $locale);
}
function __clang($translate = true, $locale = null) {
	$c = $locale ? $locale : AppManager::getInstance()->getLocale()->getLocale();
	if ($translate) {
		return __('locale.' . $c);
	}
	return $c;
}
function ___($title, $args) {
	$args = func_get_args();
	array_shift($args);
	return vsprintf(__($title), $args);
}
function __fromObject($field, $o, $locale = null, $def = null) {
	if (!is_object($o)) {
		return null;
	}
	$locale = $locale ? $locale : AppManager::getInstance()->getLocale()->getLocale();
	$lf = $field . '_' . $locale;
	if (isset($o->{$lf}) && $o->{$lf}) {
		return $o->{$lf};
	}
	return $o->{$field} ? $o->{$field} : $def;
}
function CGAFDebugOnly() {
	if (!CGAF_DEBUG) {
		throw new \Exception('DEBUG ONLY');
	}
}

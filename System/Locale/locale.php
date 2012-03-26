<?php
namespace System\Locale;
use System\Applications\IApplication;
use CGAF;
use Strings;
use System\Session\Session;
use CDate;
use Utils;

class Locale extends \BaseObject {
	private $_data = array ();
	private $_locale = null;
	private $_storePath;
	private $_debug = false;
	private $_showDefault = true;
	private $_localeExt = 'mo';
	private $_installedLocale = array ();
	private $_defaultLocale;
	private $_singleFileMode = false;
	private $_loaded =array();

	function __construct($locale, $storePath = null, $singlemode = false) {
		$this->_debug = CGAF::getConfig ( 'debug.locale', false );
		$this->_showDefault = CGAF::getConfig ( 'locale.showdefault', true );
		$this->_defaultLocale = $locale instanceof \IApplication ? $locale->getConfig ( "locale.default", "en" ) : \CGAF::getConfig ( 'locale.default', 'en' );
		$this->_singleFileMode = $singlemode;
		if ($locale instanceof IApplication) {
			$this->_storePath = $storePath ? $storePath : $locale->getAppPath () . DS . "locale" . DS;
			$this->_debug = $locale->getConfig ( 'app.debug.locale', $this->_debug );
			$this->_showDefault = $locale->getConfig ( 'app.locale.showdefault', $this->_showDefault );
		} else {
			$this->_storePath = \Utils::ToDirectory ( $storePath . DS );
			$this->_defaultLocale = $locale;
		}
		$loc = Session::get ( '_locale', $this->_defaultLocale );
		if (strlen($loc) > 5) {
			Session::set ( '_locale',$this->_defaultLocale);
		}
		$loc = Session::get ( '_locale', $this->_defaultLocale );
		$this->setlocale ( $loc );
	}
	function __destruct() {
		Session::set ( '_locale', $this->_locale );
	}
	function getDefaultLocale() {
		return $this->_defaultLocale;
	}
	function getInstalledLocale() {
		if (! $this->_installedLocale) {
			$f = Utils::getDirList ( $this->_storePath );
			$m = Utils::ToDirectory ( CGAF_PATH . DS . 'locale' . DS );
			if ($m !== $this->_storePath) {
				$fmain = Utils::getDirList ( $m );
				foreach ( $fmain as $v ) {
					if (! in_array ( $v, $f )) {
						$f [] = $v;
					}
				}
				// $f = array_intersect_assoc($f,$fmain);
			}
			foreach ( $f as $v ) {
				$this->_installedLocale [$v] = $this->_ ( 'locale.' . $v . '.title', $v );
			}
		}
		return $this->_installedLocale;
	}
	function setLocale($loc) {
		if ($loc !== $this->_locale) {
			$this->_locale = $loc;
			Session::set ( '_locale', $this->_locale );
			$this->load ( $loc, false );
		}
	}
	function getLocale() {
		return $this->_locale;
	}
	function getDateFormat($mode = "long") {
		return isset ( $this->_data ["date.format." . $mode] ) ? $this->_data ["date.format." . $mode] : "MM-dd-YYYY";
	}
	private function iget($s, $locale = null) {
		$locale = $locale ? $locale : $this->_locale;
		if (! is_string ( $s ) || strlen ( $s ) > 150) {
			return $s;
		}
		if (Strings::BeginWith ( $s, 'http:' ) || Strings::BeginWith ( $s, 'https:' )) {
			return $s;
		}
		if (! $this->_data) {
			$this->_data = array ();
		}
		if (isset ( $this->_data [$locale] [$s] )) {
			return $this->_data [$locale] [$s];
		}
		if (isset ( $this->_data [$locale] [strtolower ( $s )] )) {
			return $this->_data [$locale] [strtolower ( $s )];
		}
		return null;
	}
	public function _($s, $default = null, $context = null, $locale = null) {
		$locale = $locale ? $locale : $this->_locale;
		if ($locale !== $this->_locale) {
			$this->load ( $locale, true, null, $locale );
		}
		$retval = $this->iget ( $s, $locale );
		if (! $retval) {
			if ($default == null) {
				$default = $s;
			}
			if (strpos ( $s, "." ) > 0) {
				$context = substr ( $s, 0, strpos ( $s, "." ) );
			}
			if ($context != null) {
				$this->load ( $context, true, $context, $locale );
			}
			$retval = $this->iget ( $s );
			if ($retval == null && $context) {
				$ns = substr ( $s, strpos ( $s, "." ) + 1 );
				$retval = $this->iget ( $ns );
			}
			if ($retval === null) {
				$retval = $this->iget ( $s, $locale );
			}
		}
		$prefix = null;
		// ppd($this);
		if ($retval === null) {
			$prefix = ($this->_debug ? '^' : '');
			$retval = $this->_showDefault ? $default : $s;
		} else {
			$prefix = ($this->_debug ? '&middot;' : '');
		}
		$retval = $prefix . $retval;
		return $retval;
	}
	private function findLocaleFile($fname, $ctx, $locale) {
		if (! $this->_singleFileMode) {
			$search = array (
					$this->_storePath . $locale . DS . $fname,
					$this->_storePath . $fname,
					CGAF_PATH . DS . 'locale' . DS . $locale . DS . $fname,
					CGAF_PATH . DS . 'locale' . DS . $fname
			);
			foreach ( $search as $v ) {
				$v = $v . '.' . $this->_localeExt;
				if (is_file ( $v )) {
					return $v;
				}
			}
		} else {
			$f = $this->_storePath . $locale . DS . $fname . '.' . $this->_localeExt;
			if (is_file ( $f )) {
				return $f;
			}
		}
		return null;
	}
	function load($fname, $merge = true, $ctx = null, $locale = null) {
		$locale = $locale ? $locale : $this->getLocale ();
		if (! $merge) {
			$this->_data [$locale] = array ();
		}
		$f = $fname;
		if (in_array($fname,$this->_loaded)) {
			return;
		}
		if (! is_file ( $f )) {
			$corelangfile = CGAF_PATH . DS . 'locale' . DS . $locale . DS . $fname . '.' . $this->_localeExt;
			if (is_file ( $corelangfile )) {
				$this->load ( $corelangfile, true, null, $locale );
			}
			// if (is_file())
			$f = $this->findLocaleFile ( $fname, $ctx, $locale );
		}
		$this->_loaded[] =  $f;
		if ($f && is_file ( $f )) {
			$fp = fopen ( $f, "r" );
			while ( ($current_line = fgets ( $fp )) !== false ) {
				if (trim ( $current_line ) == "")
					continue;
				$l = explode ( "=", $current_line, 2 );
				if ($l [0] === '#include') {
					$this->load ( $l [1], true, Utils::getFileName ( $fname ), $ctx, $locale );
				}
				if (substr ( trim ( $current_line ), 0, 1 ) == '#') {
					continue;
				}
				$this->_data [$locale] [($ctx ? $ctx . "." : "") . $l [0]] = trim ( $l [1] ); // substr($l[1],0,strlen($l[1])-2);
			}
			fclose ( $fp );

		}
	}
	// TODO:: localize this function as global language
	public function number_to_text($num) {
		$stext = array (
				"Nol",
				"Satu",
				"Dua",
				"Tiga",
				"Empat",
				"Lima",
				"Enam",
				"Tujuh",
				"Delapan",
				"Sembilan",
				"Sepuluh",
				"Sebelas"
		);
		$say = array (
				"Ribu",
				"Juta",
				"Milyar",
				"Triliun",
				"Biliun",
				// ingat batasan number_format
				"--apaan---"
		); // /setelah biliun namanya apa?
		$w = "";
		if ($num < 0) {
			$w = "Minus ";
			// jadiin positive
			$num *= - 1;
		}
		$snum = number_format ( $num, 4, ",", "." );
		$strnum = explode ( ".", substr ( $snum, 0, strrpos ( $snum, "," ) ) );
		// parse decimalnya
		$koma = substr ( $snum, strrpos ( $snum, "," ) + 1 );
		$isone = substr ( $num, 0, 1 ) == 1;
		if (count ( $strnum ) == 1) {
			$num = $strnum [0];
			switch (strlen ( $num )) {
				case 1 :
				case 2 :
					if (! isset ( $stext [$strnum [0]] )) {
						if ($num < 19) {
							$w .= $stext [substr ( $num, 1 )] . " Belas";
						} else {
							$w .= $stext [substr ( $num, 0, 1 )] . " Puluh " . (intval ( substr ( $num, 1 ) ) == 0 ? "" : $stext [substr ( $num, 1 )]);
						}
					} else {
						$w .= $stext [$strnum [0]];
					}
					break;
				case 3 :
					$w .= ($isone ? "Seratus" : self::number_to_text ( substr ( $num, 0, 1 ) ) . " Ratus") . " " . (intval ( substr ( $num, 1 ) ) == 0 ? "" : self::number_to_text ( substr ( $num, 1 ) ));
					break;
				case 4 :
					$w .= ($isone ? "Seribu" : self::number_to_text ( substr ( $num, 0, 1 ) ) . " Ribu") . " " . (intval ( substr ( $num, 1 ) ) == 0 ? "" : self::number_to_text ( substr ( $num, 1 ) ));
					break;
				default :
					break;
			}
		} else {
			$text = $say [count ( $strnum ) - 2];
			$w = ($isone && strlen ( $strnum [0] ) == 1 && count ( $strnum ) <= 3 ? "Se" . strtolower ( $text ) : self::number_to_text ( $strnum [0] ) . ' ' . $text);
			array_shift ( $strnum );
			$i = count ( $strnum ) - 2;
			foreach ( $strnum as $v ) {
				if (intval ( $v )) {
					$w .= ' ' . self::number_to_text ( $v ) . ' ' . ($i >= 0 ? $say [$i] : "");
				}
				$i --;
			}
		}
		$w = trim ( $w );
		// peringatan koma harus dalam batasan integer :((
		if (intval ( $koma )) {
			$w .= " Koma " . self::number_to_text ( $koma );
		}
		return trim ( $w );
	}
	private function _parse($date, $format, $days, $months, $paramDelim = '%') {
		$dk = array_keys ( $days );
		$mk = array_keys ( $months );
		$retval = '';
		for($strpos = 0; $strpos < strlen ( $format ); $strpos ++) {
			$char = substr ( $format, $strpos, 1 );
			if ($char == $paramDelim) {
				$nextchar = substr ( $format, $strpos + 1, 1 );
				switch ($nextchar) {
					case 'D' :
						$retval .= \Utils::addChar ( $days [$dk [$date->format ( 'w' )]], '\\' );
						break;
					case 'M' : // short month
						$retval .= \Utils::addChar ( $months [$mk [$date->format ( 'n' ) - 1]], '\\' );
						break;
					default :
						$retval .= $nextchar;
						break;
				}
				$strpos ++;
			} else {
				$retval .= $char;
			}
		}
		return $date->format ( $retval );
	}
	function getDayNames($loc = null) {
		$loc = $loc ? $loc : $this->getLocale ();
		return array (
				$this->_ ( 'day.sunday.short', null, null, $loc ) => $this->_ ( 'day.sunday.long', null, null, $loc ),
				$this->_ ( 'day.monday.short', null, null, $loc ) => $this->_ ( 'day.monday.long', null, null, $loc ),
				$this->_ ( 'day.tuesday.short', null, null, $loc ) => $this->_ ( 'day.tuesday.long', null, null, $loc ),
				$this->_ ( 'day.wednesday.short', null, null, $loc ) => $this->_ ( 'day.wednesday.long', null, null, $loc ),
				$this->_ ( 'day.thursday.short', null, null, $loc ) => $this->_ ( 'day.thursday.long', null, null, $loc ),
				$this->_ ( 'day.friday.short', null, null, $loc ) => $this->_ ( 'day.friday.long', null, null, $loc ),
				$this->_ ( 'day.saturday.short', null, null, $loc ) => $this->_ ( 'day.saturday.long', null, null, $loc )
		);
	}
	function getMonthNames($loc=null) {
		return array (
				$this->_ ( 'month.january.short', null, null, $loc ) => $this->_ ( 'month.january.long', null, null, $loc ),
				$this->_ ( 'month.february.short', null, null, $loc ) => $this->_ ( 'month.february.long', null, null, $loc ),
				$this->_ ( 'month.march.short', null, null, $loc ) => $this->_ ( 'month.march.long', null, null, $loc ),
				$this->_ ( 'month.april.short', null, null, $loc ) => $this->_ ( 'month.april.long', null, null, $loc ),
				$this->_ ( 'month.may.short', null, null, $loc ) => $this->_ ( 'month.may.long', null, null, $loc ),
				$this->_ ( 'month.june.short', null, null, $loc ) => $this->_ ( 'month.june.long', null, null, $loc ),
				$this->_ ( 'month.july.short', null, null, $loc ) => $this->_ ( 'month.july.long', null, null, $loc ),
				$this->_ ( 'month.august.short', null, null, $loc ) => $this->_ ( 'month.august.long', null, null, $loc ),
				$this->_ ( 'month.september.short', null, null, $loc ) => $this->_ ( 'month.september.long', null, null, $loc ),
				$this->_ ( 'month.october.short', null, null, $loc ) => $this->_ ( 'month.october.long', null, null, $loc ),
				$this->_ ( 'month.november.short', null, null, $loc ) => $this->_ ( 'month.november.long', null, null, $loc ),
				$this->_ ( 'month.december.short', null, null, $loc ) => $this->_ ( 'month.december.long', null, null, $loc )
		);
	}
	function formatDate($date, $long = true, $oriFormat = 'Y-m-d H:i:s', $loc = null) {
		if (! $date) {
			return '';
		}
		$loc = $loc ? $loc : $this->getLocale ();
		try {
			$format = $long ? $this->_ ( 'date.format.long', '%D,  %M %d %Y %h:%i:%s', null, $loc ) : $this->_ ( 'date.format.short', '%M %d %Y', null, $loc );
			$days = $this->getDayNames($loc = null);
			$months = $this->getMonthNames($loc);
			if (! ($date instanceof \DateTime)) {
				$date = \DateTime::createFromFormat ( $oriFormat, $date );
			}
			return $this->_parse ( $date, $format, $days, $months );

		} catch ( \Exception $e ) {
			$retval = $this->_ ( 'error.invaliddate', 'Date Value not Valid' );
		}
		return $retval;
	}
}

<?php
namespace System\Locale;
use System\Applications\IApplication;
use	\CGAF;
use	\String;
use	System\Session\Session;
use \CDate;
class Locale extends \Object {
	private $_data = array ();
	private $_locale = 'en';
	private $_storePath;
	private $_debug = false;
	private $_showDefault = true;
	private $_localeExt = 'mo';
	private $_installedLocale = array ();
	function __construct($locale, $storePath = null) {
		$this->_debug = CGAF::getConfig ( 'locale.debug', CGAF_DEBUG );
		$this->_showDefault = CGAF::getConfig ( 'locale.showdefault', $this->_debug );

		if ($locale instanceof \IApplication) {
			$this->_storePath = $storePath ? $storePath : $locale->getAppPath () . DS . "locale" . DS;
			$loc = Session::get ( '_locale', $locale->getConfig ( "app.locale.default", "en" ) );
			$this->_debug = $locale->getConfig ( 'app.locale.debug', $this->_debug );
			$this->_showDefault = $locale->getConfig ( 'app.locale.showdefault', $this->_showDefault );
		} else {
			$this->_storePath = $storePath;
			$loc = $locale;
		}
		$this->setlocale ( $loc );
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

				//$f = array_intersect_assoc($f,$fmain);
			}
			foreach ( $f as $v ) {
				$this->_installedLocale [$v] = $this->_ ( 'locale.' . $v . '.title', $v );
			}
		}
		return $this->_installedLocale;
	}

	function setLocale($loc) {
		$this->_locale = $loc;
		Session::set ( '_locale', $loc );
		$this->load ( $loc, false );
	}

	function getLocale() {
		return $this->_locale;
	}

	function getDateFormat($mode = "long") {
		return isset ( $this->_data ["date.format." . $mode] ) ? $this->_data ["date.format." . $mode] : "MM-dd-YYYY";
	}

	private function iget($s) {
		if (! is_string ( $s ) || strlen ( $s ) > 50) {
			return $s;
		}
		if (String::BeginWith ( $s, 'http:' )) {
			return $s;
		}
		if (! $this->_data) {
			$this->_data = array ();
		}
		if (isset ( $this->_data [$s] )) {
			return $this->_data [$s];
		}
		if (isset ( $this->_data [strtolower ( $s )] )) {
			return $this->_data [strtolower ( $s )];
		}
		return null;
	}

	public function _($s, $default = null, $context = null) {
		$retval = $this->iget ( $s );
		if (! $retval) {

			if ($default == null) {
				$default = $s;
			}

			if (strpos ( $s, "." ) > 0) {
				$context = substr ( $s, 0, strpos ( $s, "." ) );
			}
			if ($context != null) {

				$this->load ( $context, true, $context );
			}

			$retval = $this->iget ( $s );
			if (! $retval && $context) {
				$ns = substr ( $s, strpos ( $s, "." ) + 1 );
				/*if (!isset($this->_data[$ns])) {
					pp($default);
				pp($s);
				ppd($ns);
				}*/
				$retval = $this->iget ( $ns );
			}

		}
		//ppd($this);
		if (! $retval) {
			$retval = $this->_debug ? '^' . $s . ' ' . ($this->_showDefault ? $default : '') : $default;
		} else {
			$retval = ($this->_debug ? '&middot;' : '') . $retval;
		}
		return $retval;
	}

	private function findLocaleFile($fname, $ctx) {
		$search = array (
		$this->_storePath . $this->_locale . DS . $fname,
		$this->_storePath . $fname,
		CGAF_PATH . DS . 'locale' . DS . $this->_locale . DS . $fname,
		CGAF_PATH . DS . 'locale' . DS . $fname );
		foreach ( $search as $v ) {
			$v = $v . '.' . $this->_localeExt;
			if (is_file ( $v )) {
				return $v;
			}
		}
		return null;
	}

	function load($fname, $merge = true, $ctx = null) {
		if (! $merge) {
			$this->_data = array ();
		}
		$f = $fname;
		if (is_array ( $f )) {
			ppd ( $f );
		}
		if (! is_file ( $f )) {
			$corelangfile = CGAF_PATH . DS . 'locale' . DS . $this->_locale . DS . $fname . '.' . $this->_localeExt;
			if (is_file ( $corelangfile )) {
				$this->load ( $corelangfile );
			}

			//if (is_file())
			$f = $this->findLocaleFile ( $fname, $ctx );
		}
		if ($f && is_file ( $f )) {
			$fp = fopen ( $f, "r" );
			while ( ($current_line = fgets ( $fp )) !== false ) {
				if (trim ( $current_line ) == "")
				continue;
				$l = explode ( "=", $current_line, 2 );
				if ($l [0] === '#include') {
					$this->load ( $l [1], true, Utils::getFileName ( $fname ) );
				}
				if (substr ( trim ( $current_line ), 0, 1 ) == '#') {
					continue;
				}

				$this->_data [($ctx ? $ctx . "." : "") . $l [0]] = trim ( $l [1] ); //substr($l[1],0,strlen($l[1])-2);


			}
			fclose ( $fp );
		}
	}
	//TODO:: localize this function as global language
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
			"Sebelas" );
		$say = array (
			"Ribu",
			"Juta",
			"Milyar",
			"Triliun",
			"Biliun",  // ingat batasan number_format
			"--apaan---" ); ///setelah biliun namanya apa?
		$w = "";
		if ($num < 0) {
			$w = "Minus ";
			//jadiin positive
			$num *= - 1;
		}
		$snum = number_format ( $num, 4, ",", "." );
		$strnum = explode ( ".", substr ( $snum, 0, strrpos ( $snum, "," ) ) );
		//parse decimalnya
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
		//peringatan koma harus dalam batasan integer :((
		if (intval ( $koma )) {
			$w .= " Koma " . self::number_to_text ( $koma );
		}
		return trim ( $w );
	}
	function formatDate($date, $long = true) {
		if (!$date) {
			return '';
		}
		try {
			$dt = new CDate ();

			$dt->setDate ( $date );
			$loc = $this->getLocale ();
			if ($loc == "id") {
				$days = array (
					"Senin",
					"Selasa",
					"Rabu",
					"Kamis",
					"Jum'at",
					"Sabtu",
					"Minggu" );
				$months = array (
					"Januari",
					"Februari",
					"Maret",
					"April",
					"May",
					"Juni",
					"Juli",
					"Agustus",
					"September",
					"Oktober",
					"November",
					"Desember" );
				$retval = $days [$dt->getDayOfWeek ()] . ", " . $dt->getDay () . " " . $months [$dt->getMonth () - 1] . " " . $dt->getYear ();
				if ($long) {
					$retval .= " " . $dt->getHour () . ":" . $dt->getMinute () . ":" . $dt->getSecond ();
				}
			} else {
				$format = __ ( "date.format." . ($long ? "long" : "short") );
				$retval = $dt->format ( $date, $format );
			}
		} catch ( Exception $e ) {
			$retval = $this->_ ( 'error.invaliddate', 'Date Value not Valid' );
		}
		return $retval;
	}
}

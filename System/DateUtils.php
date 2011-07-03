<?php

abstract class DateUtils {
	public static function now($format, $offset = +7, $timestamp = null) {
		$timestamp = $timestamp ? $timestamp : time ();
		//Offset is in hours from gmt, including a - sign if applicable.
		//So lets turn offset into seconds
		$offset = $offset * 60 * 60;
		$timestamp = $timestamp + $offset;
		//Remember, adding a negative is still subtraction ;)
		return gmdate ( $format, $timestamp );

	}
	public static function DateDiff($date, $date2 = null) {
		$date2 = $date2 ? $date2 : new CDate ();
		$date = new CDate ( $date );
		return $date->dateDiff ( $date2 );
	}
	public static function toDate($date) {
		if (is_object ( $date )) {
			return $date;
		}
		$dt = explode ( "/", $date );


		if (count ( $dt ) >= 3) {
			$date = new CDate ();
			try {
				$date->setDayMonthYear ( $dt [0], $dt [1], $dt [2] );
			} catch ( Exception $e ) {
				$date->setDayMonthYear ( $dt [1], $dt [0], $dt [2] );
			}
		} else {
			$date = new CDate ( $date );
		}
		return $date;

	}
	public static function add($date, $long, $mode = 'day', $format = FMT_TIMESTAMP) {
		$date = new CDate ( $date );
		switch ($mode) {
			case 'd' :
			case 'day' :
				$date->addDays ( $long );
				break;
			case 'month' :
			case 'M' :
				$date->addMonths ( $long );
				break;
			default :
				throw new SystemException ( 'Unknown Mode ' . $mode );
				break;
		}
		return $date->format ( $format );
	}
	public static function ago($d) {
		if ($d ==null) {
			return 'Unknown';
		}
		if (is_object($d)) {
			$d = strtotime($d->format(FMT_DATEISO));
		}elseif (is_string($d)) {
			$d = strtotime ( $d );
		}
		$difference = time () - $d;
		//ppd($difference);
		$periods = array ("second", "minute", "hour", "day", "week", "month", "years", "decade" );
		$lengths = array (60, 60, 24, 7, 4.35, 12, 10 );
		for($j = 0; $difference >= $lengths [$j]; $j ++) {
			if ((int)$lengths [$j] >0) {
				$difference /= $lengths [$j];
			}else{
				break;
			}
		}
		$difference = round ( $difference );
		if ($difference != 1)
			$periods [$j] .= "s";
		if ($difference == 0) {
			$difference = 'a';
		}
		$text = "$difference ".ucfirst($periods[$j])." ago";
		return $text;
	}
	public static function formatDate($date, $long = true) {
		return CGAF::getLocale ()->formatDate ( $date, $long );
	}
	public static function DateToUnixTime($time) {

		if (preg_match('/(\d{4})(\d{2})(\d{2})(\d{2})(\d{2})(\d{2})/', $time, $pieces) || preg_match('/(\d{2})(\d{2})(\d{2})(\d{2})(\d{2})(\d{2})/', $time, $pieces)) {
			$unix_time = mktime($pieces [4], $pieces [5], $pieces [6], $pieces [2], $pieces [3], $pieces [1]);
		} elseif (preg_match('/\d{4}\-\d{2}\-\d{2} \d{2}:\d{2}:\d{2}/', $time) || preg_match('/\d{2}\-\d{2}\-\d{2} \d{2}:\d{2}:\d{2}/', $time) || preg_match('/\d{4}\-\d{2}\-\d{2}/', $time) || preg_match('/\d{2}\-\d{2}\-\d{2}/', $time)) {
			$unix_time = strtotime($time);
		} elseif (preg_match('/(\d{4})(\d{2})(\d{2})/', $time, $pieces) || preg_match('/(\d{2})(\d{2})(\d{2})/', $time, $pieces)) {
			$unix_time = mktime(0, 0, 0, $pieces [2], $pieces [3], $pieces [1]);
		}

		return $unix_time;
	}
	public static function gmFormat($format) {
		return str_replace('%','',$format);
	}
	public static function isDate($o) {
		try {
			$d = new CDate($o);
			return true;
		}catch(Exception $e) {

		}
		return false;
	}
}
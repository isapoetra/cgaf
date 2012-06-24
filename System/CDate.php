<?php
if (!defined('CGAF'))
	die ('Restricted Access');
// using("Libs.PEAR");
// Pear::load("Date");
define ('FMT_DATEISO', '%Y%m%dT%H%M%S');
define ('FMT_DATELDAP', '%Y%m%d%H%M%SZ');
/**
 *
 * @var string
 * @deprecated Please Use \CDate::FMT_DATETIME_MYSQL
 */
define ('FMT_DATETIME_MYSQL', 'Y-m-d H:i:s');
define ('FMT_DATERFC822', '%a, %d %b %Y %H:%M:%S');
define ('FMT_TIMESTAMP', '%Y%m%d%H%M%S');
define ('FMT_TIMESTAMP_DATE', '%Y%m%d');
define ('FMT_TIMESTAMP_TIME', '%H%M%S');
define ('FMT_UNIX', '3');
define ('WDAY_SUNDAY', 0);
define ('WDAY_MONDAY', 1);
define ('WDAY_TUESDAY', 2);
define ('WDAY_WEDNESDAY', 3);
define ('WDAY_THURSDAY', 4);
define ('WDAY_FRIDAY', 5);
define ('WDAY_SATURDAY', 6);
define ('SEC_MINUTE', 60);
define ('SEC_HOUR', 3600);
define ('SEC_DAY', 86400);
class CDate extends \DateTime {
	const FMT_DATETIME_MYSQL='Y-m-d H:i:s';
	function __construct($date = null, DateTimeZone $timeZone = null) {
		if (!$timeZone) {
			$tz = date_default_timezone_get();
			$timeZone =new DateTimeZone($tz);
		}
		if ($date) {
			if (is_string($date)) {
				if (is_numeric($date)) { // treat as timestamp
					$date = new \DateTime (date('Y-m-d H:i:s', $date), $timeZone);

				} else {
					$date = new \DateTime ($date, $timeZone);
					// ppd ( $date );
				}
			}elseif (is_numeric($date)) {
				parent::__construct(null,$timeZone);
				$this->setTimestamp($date);
				return;
			}
			if (is_object($date)) {
				if ($date instanceof \DateTime) {
					$format = DATE_ISO8601;
					$date = $date->format('Y-m-d H:i:s');
				}
			}
		}
		if (is_object($date)) {
			ppd($date);
		}
		parent::__construct($date, $timeZone);
	}

	/**
	 * Overloaded compare method
	 * The convertTZ calls are time intensive calls.
	 * When a compare call is
	 * made in a recussive loop the lag can be significant.
	 *
	 * @param      $d1
	 * @param      $d2
	 * @param bool $convertTZ
	 * @return int
	 * @deprecated
	 */
	function compare(\DateTime $d1, \DateTime $d2, $convertTZ = false) {
		if ($convertTZ) {
			$d1->convertTZ(new DateTimeZone ('UTC'));
			$d2->convertTZ(new DateTimeZone ('UTC'));
		}
		$days1 = Date_Calc::dateToDays($d1->day, $d1->month, $d1->year);
		$days2 = Date_Calc::dateToDays($d2->day, $d2->month, $d2->year);
		if ($days1 < $days2)
			return -1;
		if ($days1 > $days2)
			return 1;
		if ($d1->hour < $d2->hour)
			return -1;
		if ($d1->hour > $d2->hour)
			return 1;
		if ($d1->minute < $d2->minute)
			return -1;
		if ($d1->minute > $d2->minute)
			return 1;
		if ($d1->second < $d2->second)
			return -1;
		if ($d1->second > $d2->second)
			return 1;
		return 0;
	}

	function addDays($n) {
		return parent::add(new DateInterval ('P' . $n . 'D'));
	}

	function addMonths($n) {
		return parent::add(new DateInterval ('P' . $n . 'Y'));
	}

	function formatDiff(\DateInterval $diff) {
		$ret = array();


		if ($diff->y > 0) {
			$ret[] = $diff->y . ' '. __('date.diff.years','Year(s)');
		}
		if ($diff->m > 0) {
			$ret[] = $diff->m .  ' '. __('date.diff.months','Month(s)');
		}
		if ($diff->d > 0) {
			$ret[] = $diff->d . ' '. __('date.diff.days','Day(s)');
		}
		if ($diff->h > 0) {
			$ret[] = $diff->h . ' '. __('date.diff.hours','Hour(s)');
		}
		if ($diff->i > 0) {
			$ret[] = $diff->i . ' '. __('date.diff.minutes','Minute(s)');
		}
		if ($diff->s > 0) {
			$ret[] = $diff->s . ' '. __('date.diff.seconds','Second(s)');
		}
		if ($diff->invert) {
			$ret[] = 'ago';
		}
		return implode(' ', $ret);
	}

	function dateDiff($to, $string = true) {
		$diff = parent::diff(new \CDate ($to));
		$format = '%R%a';
		if ($string) {
			$ret = $this->formatDiff($diff);
			//ppd($dif);
		} else {
			$ret = $diff; //->format ( $format );
		}
		return $ret;
	}

	function isWorkingDay() {
		global $AppUI;
		$working_days = CGAF::getConfig("date.cal_working_days");
		if (is_null($working_days)) {
			$working_days = array(
					'1',
					'2',
					'3',
					'4',
					'5'
			);
		} else {
			$working_days = explode(",", $working_days);
		}
		return in_array($this->getDayOfWeek(), $working_days);
	}

	function getDayOfWeek() {
		return (int)$this->format('w');
	}

	function inRange($start, $end) {
		$start = new CDate ($start);
		$end = new CDate ($end);
		$ts = $this->getTimestamp();
		return $ts >= $start->getTimestamp() && $ts <= $end->getTimestamp();
	}

	function getAMPM() {
		if ($this->getHour() > 11) {
			return "pm";
		} else {
			return "am";
		}
	}

	/*
	 * Return date obj for the end of the next working day *
	* @param	bool	Determine whether to set time to start of day or preserve the
	* time of the given object
	*/
	function next_working_day($preserveHours = false) {
		global $AppUI;
		$do = $this;
		$end = intval(CGAF::getConfig('date.cal_day_end'));
		$start = intval(CGAF::getConfig('date.cal_day_start'));
		while (!$this->isWorkingDay() || $this->getHour() > $end || ($preserveHours == false && $this->getHour() == $end && $this->getMinute() == '0')) {
			$this->addDays(1);
			$this->setTime($start, '0', '0');
		}
		if ($preserveHours)
			$this->setTime($do->getHour(), '0', '0');
		return $this;
	}

	/**
	 * @return int
	 */
	function getHour() {
		return (int)$this->format('%h');
	}

	/**
	 * @return int
	 */
	function getMinute() {
		return (int)$this->format('%i');
	}

	/*
	 * Return date obj for the end of the previous working day *
	* @param	bool	Determine whether to set time to end of day or preserve the
	* time of the given object
	*/
	function prev_working_day($preserveHours = false) {
		global $AppUI;
		$do = $this;
		$end = intval(CGAF::getConfig('date.cal_day_end'));
		$start = intval(CGAF::getConfig('date.cal_day_start'));
		while (!$this->isWorkingDay() || ($this->getHour() < $start) || ($this->getHour() == $start && $this->getMinute() == '0')) {
			$this->addDays(-1);
			$this->setTime($end, '0', '0');
		}
		if ($preserveHours)
			$this->setTime($do->getHour(), '0', '0');
		return $this;
	}

	/*
	 * Calculating _robustly_ a date from a given date and duration * Works in
	* both directions: forwards/prospective and backwards/retrospective *
	* Respects non-working days * @param	int	duration	(positive = forward,
			* negative = backward) * @param	int	durationType; 1 = hour; 24 = day; *
	* @return	obj	Shifted DateObj
	*/
	function addDuration($duration = '8', $durationType = '1') {
		// using a sgn function lets us easily cover
		// prospective and retrospective calcs at the same time
		// get signum of the duration
		$sgn = dPsgn($duration);
		// make duration positive
		$duration = abs($duration);
		// in case the duration type is 24 resp. full days
		// we're finished very quickly
		if ($durationType == '24') {
			$full_working_days = $duration;
		} else if ($durationType == '1') { // durationType is 1 hour
			// get dP time constants
			$cal_day_start = intval(CGAF::getConfig('date.cal_day_start'));
			$cal_day_end = intval(CGAF::getConfig('date.cal_day_end'));
			$dwh = intval(CGAF::getConfig('date.daily_working_hours'));
			// move to the next working day if the first day is a non-working
			// day
			($sgn > 0) ? $this->next_working_day() : $this->prev_working_day();
			// calculate the hours spent on the first day
			$firstDay = ($sgn > 0) ? min($cal_day_end - $this->hour, $dwh) : min($this->hour - $cal_day_start, $dwh);
			/*
			 * * Catch some possible inconsistencies: * If we're later than
			* cal_end_day or sooner than cal_start_day * just move by one day
			* without subtracting any time from duration
			*/
			if ($firstDay < 0)
				$firstDay = 0;
			// Intraday additions are handled easily by just changing the
			// hour value
			if ($duration <= $firstDay) {
				($sgn > 0) ? $this->setHour($this->hour + $duration) : $this->setHour($this->hour - $duration);
				return $this;
			}
			// the effective first day hours value
			$firstAdj = min($dwh, $firstDay);
			// subtract the first day hours from the total duration
			$duration -= $firstAdj;
			// we've already processed the first day; move by one day!
			$this->addDays(1 * $sgn);
			// make sure that we didn't move to a non-working day
			($sgn > 0) ? $this->next_working_day() : $this->prev_working_day();
			// end of proceeding the first day
			// calc the remaining time and the full working days part of this
			// residual
			$hoursRemaining = ($duration > $dwh) ? ($duration % $dwh) : $duration;
			$full_working_days = round(($duration - $hoursRemaining) / $dwh);
			// (proceed the full days later)
			// proceed the last day now
			// we prefer wed 16:00 over thu 08:00 as end date :)
			if ($hoursRemaining == 0) {
				$full_working_days--;
				($sgn > 0) ? $this->setHour($cal_day_start + $dwh) : $this->setHour($cal_day_end - $dwh);
			} else
				($sgn > 0) ? $this->setHour($cal_day_start + $hoursRemaining) : $this->setHour($cal_day_end - $hoursRemaining);
			// end of proceeding the last day
		}
		// proceeding the fulldays finally which is easy
		// Full days
		for ($i = 0; $i < $full_working_days; $i++) {
			$this->addDays(1 * $sgn);
			if (!$this->isWorkingDay()) // just 'ignore' this non-working day
				$full_working_days++;
		}
		// end of proceeding the fulldays
		return $this->next_working_day();
	}

	function copy(CDate $src) {
		$this->setTimestamp($src->getTimestamp());
	}

	/*
	 * Calculating _robustly_ the working duration between two dates * * Works
	* in both directions: forwards/prospective and backwards/retrospective *
	* Respects non-working days * * * @param	obj	DateObject	may be viewed as
	* end date * @return	int							working duration in hours
	*/
	function calcDuration($e) {
		// since one will alter the date ($this) one better copies it to a new
		// instance
		$s = new CDate ();
		$s->copy($this);
		// get dP time constants
		$cal_day_start = intval(CGAF::getConfig('date.cal_day_start'));
		$cal_day_end = intval(CGAF::getConfig('date.cal_day_end'));
		$dwh = intval(CGAF::getConfig('date.daily_working_hours'));
		// assume start is before end and set a default signum for the duration
		$sgn = 1;
		// check whether start before end, interchange otherwise
		if ($e->before($s)) {
			// calculated duration must be negative, set signum appropriately
			$sgn = -1;
			$dummy = $s;
			$s->copy($e);
			$e = $dummy;
		}
		// determine the (working + non-working) day difference between the two
		// dates
		$days = $e->dateDiff($s);
		// if it is an intraday difference one is finished very easily
		if ($days == 0)
			return min($dwh, abs($e->hour - $s->hour)) * $sgn;
		// initialize the duration var
		$duration = 0;
		// process the first day
		// take into account the first day if it is a working day!
		$duration += $s->isWorkingDay() ? min($dwh, abs($cal_day_end - $s->hour)) : 0;
		$s->addDays(1);
		// end of processing the first day
		// calc workingdays between start and end
		for ($i = 1; $i < $days; $i++) {
			$duration += $s->isWorkingDay() ? $dwh : 0;
			$s->addDays(1);
		}
		// take into account the last day in span only if it is a working day!
		$duration += $s->isWorkingDay() ? min($dwh, abs($e->hour - $cal_day_start)) : 0;
		return $duration * $sgn;
	}

	function workingDaysInSpan($e) {
		global $AppUI;
		// assume start is before end and set a default signum for the duration
		$sgn = 1;
		// check whether start before end, interchange otherwise
		if ($e->before($this)) {
			// duration is negative, set signum appropriately
			$sgn = -1;
		}
		$wd = 0;
		$days = $e->dateDiff($this);
		$start = $this;
		for ($i = 0; $i <= $days; $i++) {
			if ($start->isWorkingDay())
				$wd++;
			$start->addDays(1 * $sgn);
		}
		return $wd;
	}
	public function isFuture() {
		$now = new CDate();
		return  $now->getTimestamp() > $this->getTimestamp();
	}
	public static function Current($fmt = DATE_ISO8601) {
		$dt = new CDate ();
		return $dt->format($fmt);
	}

	public static function getMFileTime($filePath) {
		$time = filemtime($filePath);
		$isDST = (date('I', $time) == 1);
		$systemDST = (date('I') == 1);
		$adjustment = 0;
		if ($isDST == false && $systemDST == true)
			$adjustment = 3600;
		else if ($isDST == true && $systemDST == false)
			$adjustment = -3600;
		else
			$adjustment = 0;
		return ($time + $adjustment);
	}
	function getYear() {
		ppd($this);
	}
}

?>

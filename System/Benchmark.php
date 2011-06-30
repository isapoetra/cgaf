<?php
defined ( "CGAF" ) or die ( "Restricted Access" );
class Benchmark {
	private $checkpoint = array ();
	private $time_epleased = 0;
	private static $_instance = null;
	function benchmark() {
		$this->func ['round'] = create_function ( '$a,$b', '' );

		$this->checkpoint ();
	}
	function round($a, $b) {
		return number_format ( ($b - $a), 10 );
	}
	function mtime() {
		list ( $usec, $sec ) = explode ( " ", microtime () );
		return ($sec + $usec);
	}
	function checkpoint($comment = '', $mark = 2) {
		$this->checkpoint [] = array ('comment' => $comment, 'time' => $this->mtime (), 'mark' => $mark );
	}

	function report($report = false) {
		$this->checkpoint (); // let's calculate all the code before the report printing


		$output = '';
		$tot = count ( $this->checkpoint );
		$last = $tot - 1;

		$this->time_epleased = $this->round( $this->checkpoint [0] ['time'], $this->checkpoint [$last] ['time'] );
		for($id = 1; $id < $last; $id ++) {
			$prev = $id - 1;

			if (! $this->checkpoint [$id] ['comment'])
			$this->checkpoint [$id] ['comment'] = 'Checkpoint #' . ($id + 1);

			if ($this->checkpoint [$id] ['mark'] === null) {
				$time_to_jump = ($id == 1) ? $this->round ( $this->checkpoint [0] ['time'], $this->checkpoint [$id] ['time'] ) : $this->func ['round'] ( $this->checkpoint [$prev] ['time'], $this->checkpoint [$id] ['time'] );
				$this->time_epleased -= $time_to_jump;

				unset ( $time_to_jump );

				continue;
			}
		}

		if (!$report)
		return number_format ( $this->time_epleased, 4 );
		else
		return ( string ) $output;
	}
	private static function getInstance() {
		if (self::$_instance == null) {
			self::$_instance = new Benchmark ( );
		}
		return self::$_instance;
	}
	public static function mark($msg, $mark = 2) {
		return self::getInstance ()->checkpoint ( $msg, $mark );
	}
	public static function Flush($detail = false) {
		return self::getInstance ()->Report ( $detail );
	}
}

?>
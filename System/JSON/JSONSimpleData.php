<?php
namespace System\JSON;
class  JSONSimpleData {
	public $results;
	public $rows;
	function __construct($rows = array()){
		$this->results =0;
		$this->rows = $rows;
	}
}
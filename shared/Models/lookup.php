<?php
namespace System\Models;
use System\MVC\Model;
use \CGAF;

class Lookup extends Model {
	/**
	 *
	 * @var
	 * @FieldType VARCHAR
	 * @FieldWidth 50
	 */
	public $app_id;
	/**
	 *
	 * @var
	 * @FieldType VARCHAR
	 * @FieldWidth 45
	 */
	public $lookup_id;
	/**
	 *
	 * @var
	 * @FieldType VARCHAR
	 * @FieldWidth 45
	 */
	public $key;
	/**
	 *
	 * @var
	 * @FieldType VARCHAR
	 * @FieldWidth 250
	 */
	public $value;
	/**
	 *
	 * @var
	 * @FieldType VARCHAR
	 * @FieldWidth 45
	 */
	public $descr;
	function __construct() {
		parent::__construct(CGAF::getDBConnection(),'lookup',array('app_id','lookup_id','key'),true);
	}
	protected function getLookupId() {
		return null;
	}
	function getLookup($lookupid=null,$where=null) {
		$lookupid =  $lookupid==null ? $this->getLookupId() : $lookupid;
		$this->clear();
		$this->Where('lookup_id='.$this->quote($lookupid));
		if ($where) {
			$this->where($where);
		}
		return $this->loadObjects();
	}
}
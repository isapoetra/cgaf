<?php
namespace System\Models;
use System\MVC\Model;
use CGAF;
class Lookup extends Model {
	/**
	 * @FieldType VARCHAR
	 * @FieldWidth 50
	 *
	 * @var
	 *
	 *
	 */
	public $app_id;
	/**
	 * @FieldType VARCHAR
	 * @FieldWidth 45
	 *
	 * @var
	 *
	 *
	 */
	public $lookup_id;
	/**
	 * @FieldType VARCHAR
	 * @FieldWidth 45
	 *
	 * @var string
	 *
	 */
	public $key;
	/**
	 * @FieldType VARCHAR
	 * @FieldWidth 250
	 *
	 * @var mixed
	 *
	 */
	public $value;
	/**
	 * @FieldType VARCHAR
	 * @FieldWidth 45
	 *
	 * @var
	 *
	 *
	 */
	public $descr;
	function __construct() {
		parent::__construct ( CGAF::getDBConnection (), 'lookup', array (
				'app_id',
				'lookup_id',
				'key'
		), true, \CGAF::isInstalled () === false );
	}
	protected function getLookupId() {
		return null;
	}
	function getLookup($lookupid = null, $where = null) {
		$lookupid = $lookupid == null ? $this->getLookupId () : $lookupid;
		$this->clear ();
		$this->Where ( 'lookup_id=' . $this->quote ( $lookupid ) );
		if ($where) {
			$this->where ( $where );
		}
		return $this->loadObjects ();
	}
}
<?php
namespace System\Models;
use System\MVC\Models\ExtModel;
use System\MVC\Model;

class recentlogModel extends ExtModel {
	/**
	 * @FieldExtra NOT NULL AUTO_INCREMENT
	 *
	 * @var int
	 */
	public $id;
	/**
	 * @FieldLength 50
	 * @var string
	 */
	public $app_id;

	/**
	 * @FieldLength 150
	 *
	 * @var string
	 */
	public $title;
	/**
	 * @FieldType text
	 *
	 * @var string
	 */
	public $descr;

	function __construct() {
		parent::__construct(\CGAF::getDBConnection(), 'recentlog', 'id', true, \CGAF::isInstalled() === false);
	}
}

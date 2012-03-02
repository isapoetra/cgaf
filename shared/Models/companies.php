<?php
namespace System\Models;
use System\MVC\Model;
class Companies extends Model {
	/**
	 * @FieldType int
	 * @FieldExtra auto_increment
	 *
	 * @var int
	 */
	public $company_id;
	/**
	 * @FieldType int
	 *
	 * @var int
	 */
	public $company_module;
	/**
	 * @FieldType varchar
	 * @FieldLength 100
	 *
	 * @var string
	 */
	public $company_name;
	/**
	 * @FieldType varchar
	 * @FieldLength 30
	 *
	 * @var string
	 */
	public $company_phone1;
	/**
	 * @FieldType varchar
	 * @FieldLength 30
	 *
	 * @var string
	 */
	public $company_phone2;
	/**
	 * @FieldType varchar
	 * @FieldLength 30
	 *
	 * @var string
	 */
	public $company_fax;
	/**
	 * @FieldType varchar
	 * @FieldLength 50
	 *
	 * @var string
	 */
	public $company_address1;
	/**
	 * @FieldType varchar
	 * @FieldLength 50
	 *
	 * @var string
	 */
	public $company_address2;
	/**
	 * @FieldType varchar
	 * @FieldLength 30
	 *
	 * @var string
	 */
	public $company_city;
	/**
	 * @FieldType varchar
	 * @FieldLength 30
	 *
	 * @var string
	 */
	public $company_state;
	/**
	 * @FieldType varchar
	 * @FieldLength 11
	 *
	 * @var string
	 */
	public $company_zip;
	/**
	 * @FieldType varchar
	 * @FieldLength 255
	 *
	 * @var string
	 */
	public $company_primary_url;
	/**
	 * @FieldType int
	 *
	 * @var int
	 */
	public $company_owner;
	/**
	 * @FieldType text
	 *
	 * @var string
	 */
	public $company_description;
	/**
	 * @FieldType smallint
	 * @FieldDefaultValue 0
	 *
	 * @var int
	 */
	public $company_type;
	/**
	 * @FieldType varchar
	 * @FieldLength 255
	 *
	 * @var string
	 */
	public $company_email;
	/**
	 * @FieldType longtext
	 *
	 * @var string
	 */
	public $company_custom;
	/**
	 * @FieldType varchar
	 * @FieldLength 50
	 *
	 * @var string
	 */
	public $company_logo;
	function __construct() {
		parent::__construct ( \CGAF::getDBConnection (), 'companies', 'company_id', false, \CGAF::isInstalled () === false );
	}
}
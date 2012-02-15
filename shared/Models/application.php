<?php
namespace System\Models;
use System\MVC\Model;

use \CGAF,\System\ACL\ACLHelper;
use \System\DB\Table;
/**
 *
 * @author Iwan Sapoetra
 * @DBEngine InnoDB
 */
class Application extends Model implements \IApplicationInfo {
	/**
	 * @FieldType varchar
	 * @FieldLength 50
	 * @var String
	 * @FieldIsPrimarykey true
	 * @FieldArgs NOT NULL PRIMARY KEY UNIQUE
	 */
	public $app_id;

	/**
	 * @FieldType varchar
	 * @FieldLength 150
	 * @FieldAllowNull false
	 * @var String
	 */
	public $app_name;
	/**
	 * @FieldType boolean
	 * @var boolean
	 */
	public $active;

	/**
	 * @FieldType varchar
	 * @FieldLength 250
	 * @var String
	 */
	public $app_path;

	public $app_version;
	public $app_icon;
	public $app_class_name;
	public $app_state;
	public $app_descr;
	function __construct($connection) {
		parent::__construct($connection, "applications", "app_id", false);
		//$connection->exec('alter table applications add (app_descr varchar(250))');
		$this->_notAllowNull = array(
				"app_name");
	}
	function filterACL($o) {
		if (is_object($o)) {
			if ((int)$o->app_id===-1 || $o->app_id==='__cgaf') {
				return $o;
			}
			$r = CGAF::isAllow($o->app_id,ACLHelper::APP_GROUP);

			if ($r) {
				return $o;
			}
			return null;
		}
		return parent::filterACL($o);
	}
	function getAppId() {
		return $this->app_id;
	}
	function getAppName() {
		return $this->app_name;
	}
	function getAppPath() {
		return $this->app_path;
	}
	function getAppVersion() {
		return $this->app_version;
	}
}
?>
<?php
namespace System\Models;
use System\MVC\Models\ExtModel;

use System\MVC\Model;
class NewsModel extends ExtModel {
	public $id;
	/**
	 * @FieldLength 50
	 * @var string
	 */
	public $app_id;
	/**
	 * @FieldLength 50
	 * @var string
	 */
	public $controller;
	/**
	 * @FieldLength 50
	 * @var string
	 */
	public $item;
	/**
	 * @FieldLength 250
	 * @FieldType varchar
	 * @var string
	 */
	public $title;
	/**
	 * @FieldType smallint
	 * @FieldDefaultValue 0
	 * @var int
	 */
	public $type;
	function __construct() {
		parent::__construct(\CGAF::getDBConnection(), 'news','id',true,true);
	}
	function reset($mode=null,$id=null) {
		$this->setAlias('n');
		switch ($mode) {
			case 'detail':
				$this->clear('field');
				$this->select('n.*,ui.fullname');
				$this->join('vw_userinfo', 'ui', 'n.user_created=ui.user_id','inner',true);
				break;
		}
		return $this;
	}
}
?>
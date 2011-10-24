<?php
namespace System\Models;
use System\MVC\Model;
class LikeModel extends Model {
	public $like_id;
	public $like_type;
	public $like_item;
	public $app_id;
	public $count;
	function __construct() {
		parent::__construct(\CGAF::getDBConnection(), 'like', 'like_id');
		$this->setAlias('l');
	}
	function getCountFor($type, $item, $app) {
		//TODO cache
		$this->clear();
		$this->where('like_type=' . $this->quote($type));
		$this->where('like_item=' . $this->quote($item));
		$this->where('app_id=' . $this->quote($app));
		$o = $this->loadObject();
		return $o ? $o->count : 0;
	}
}

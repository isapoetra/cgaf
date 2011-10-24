<?php
namespace System\Models;
use System\ACL\ACLHelper;
use System\Exceptions\AccessDeniedException;
use System\MVC\ExtModel;
class CommentModel extends ExtModel {
	public $comment_id;
	public $comment_parent = 0;
	public $app_id;
	public $comment_title;
	public $comment_descr;
	public $comment_type;
	public $comment_item;
	public $comment_status;
	function __construct() {
		parent::__construct(\CGAF::getDBConnection(), 'comment', 'comment_id', true);
	}
	function getRowCount($type, $item) {
		$this->Clear();
		$this->where('comment_type=' . $this->quote($type));
		$this->where('comment_item=' . $this->quote($item));
		if (!ACLHelper::isInrole(ACLHelper::ADMINS_GROUP)) {
			$this->where('comment_status=1');
		}
		return parent::getRowCount(false);
	}
	function check($mode = null) {
		$mode = $this->getCheckMode($mode);
		switch ($mode) {
		case self::MODE_INSERT:
			$this->app_id = $this->getAppOwner()->getAppId();
			break;
		case self::MODE_DELETE:
			ppd($this);
		}
		return parent::check($mode);
	}
	function loadChild($type, $item, $parent) {
		$this->reset();
		$this->where('comment_type=' . $this->quote($type));
		$this->where('comment_item=' . $this->quote($item));
		$this->where('comment_parent=' . (int) $parent);
		$retval = $this->loadObjects();
		foreach ($retval as $r) {
			$r->childs = $this->loadChild($type, $item, $r->comment_id);
		}
		return $retval;
	}
	private function deleteChild($parent) {
		$this->clear();
		$this->where('comment_parent=' . $parent);
		$o = $this->loadObjects();
		if ($o) {
			foreach ($o as $r) {
				$this->delete($r->comment_id);
			}
		}
		return true;
	}
	function delete($id = null) {
		$id = $id ? $id : \Request::get('id');
		if (parent::delete($id)) {
			$this->deleteChild($id);
			return true;
		}
		return false;
	}
	private function undeleteChild($parent) {
		$this->clear();
		$this->where('comment_parent=' . $parent);
		$o = $this->loadObjects();
		if ($o) {
			foreach ($o as $r) {
				$this->undel($r->comment_id);
			}
		}
		return true;
	}
	function undel($id = null) {
		$id = $id ? $id : \Request::get('id');
		if (parent::undel($id)) {
			$this->undeleteChild($id);
			return true;
		}
		return false;
	}
	function recent($type, $item = null, $status = 1) {
		$this->reset('recent');
		$this->where('comment_type=' . $this->quote($type));
		if ($item !== null) {
			$this->where('comment_item=' . $this->quote($item));
		}
		if ($status !== null) {
			$this->reset()->where('comment_status=' . $status);
		}
		return $this;
	}
}

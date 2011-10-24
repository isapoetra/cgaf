<?php
namespace System\MVC;
use System\ACL\ACLHelper;
class ExtModel extends Model {
	public $user_created;
	public $date_created;
	public $user_modified;
	public $date_modified;
	public $data_state = 1;
	function check($mode = null) {
		$mode = $this->getCheckMode($mode);
		switch ($mode) {
		case self::MODE_INSERT:
			$this->user_created = ACLHelper::getUserId();
			$this->user_modified = ACLHelper::getUserId();
			$this->date_created = $this->getConnection()->DateToDB();
			$this->date_modified = $this->getConnection()->DateToDB();
			$this->data_state = 1;
			break;
		}
		return parent::check($mode);
	}
	function getRowCount($clean = true) {
		if ($clean) {
			$this->clear();
		}
		$this->where('data_state=1');
		return parent::getRowCount();
	}
	function undel($id = null) {
		$id = $id ? $id : \Request::get('id');
		if ($id == '0') {
			throw new AccessDeniedException();
		}
		$m = $this->load($id, true);
		if ((int) $m->data_state !== 999) {
			$this->setLastError('data already restored');
			return false;
		}
		$m->update('data_state', 1);
		$m->exec();
		return true;
	}
	function delete($id = null) {
		$id = $id ? $id : \Request::get('id');
		if ($id == '0')
			throw new AccessDeniedException();
		$this->clear();
		$m = $this->load($id, true);
		if ((int) $m->data_state === 999) {
			$this->setLastError('data already deleted');
			return false;
		}
		$m->update('data_state', 999);
		$m->exec();
		return true;
	}
}

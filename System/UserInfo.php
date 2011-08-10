<?php
class UserInfo {
	private $_info;
	private $_owner;
	private $_friend;
	function __construct(\IApplication $owner,$id) {
		$this->_id = $id;
		$this->_info = $owner->getModel('user')
		->getUserInfo($id);
		$this->_owner = $owner;
	}
	function __get($v) {
		return $this->_info->$v;
	}
	function setConfig($config,$val) {
		return $this->_owner->setUserConfig($config,$val,$this->_id) ;
	}
	function getConfig($config,$def=null) {
		return $this->_owner->getUserConfig($config,$def,$this->_id);
	}

	function addFriend($id) {
		if (!$this->isFriendOf($id)) {
			$m = $this->_owner->getModel('friend');
			$m->to_person = $id;
			$m->connect_type = 1;
			//TODO should recheck for security
			if ($m->store()) {
				$this->_friend =null;
			}
		}
		return true;
	}
	private function getStatus($id=null) {
		$id = $id == null  ? ACLHelper::getUserId() : $id;
		if (!$this->_wall) {
			$m =$this->_owner
			->getModel('user_wall')
			->clear();
			
			$this->_wall = $m
			->where('user_id='.$m->quote($id))
			->orderby('date_publish desc')
			->loadAll();
		}
		return $this->_wall;


	}
	function getLastStatus() {
		$st = $this->getStatus();
		ppd($st);

	}

	function getFriendList($id=null) {
		$id = $id == null  ? ACLHelper::getUserId() : $id;
		if (!$this->_friend) {
			$this->_friend = $this->_owner
			->getModel('friend')
			->loadFriend($id);
		}
		return $this->_friend;

	}
	function isFriendOf($id = null) {
		return array_key_exists($this->getFriendList(),$id);
	}
}
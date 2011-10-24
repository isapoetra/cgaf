<?php
class FriendModel extends MVCModel {
	public $person_id;
	public $to_person;
	public $connect_type;
	public $state;
	function __construct($connection) {
		parent::__construct($connection,'personstopersons',array('person_id','to_person'));
	}
	function check($mode = null) {
		$mode = $this->getCheckMode ( $mode );
		switch ($mode) {
			case 'insert':
				$this->person_id =  $this->person_id ==null ? ACLHelper::getUserId() : $this->person_id;
				if (!$this->to_person) {
					$this->setLastError('invalid to person');
					return false;
				}
				break;
		}
		return parent::check($mode);
	}
	function loadFriend($id=null) {
		$id = $id==null ? $this->getAppOwner()->getACL()->getLogonInfo()->person_id : $id;
		$ret = $this->clear()
		->where('person_id='.$this->quote($id))
		->loadObjects();
		$retval = array();
		foreach($ret as $row) {
			$retval[$row->to_person] = $row;
		}
		return $retval;
	}
}
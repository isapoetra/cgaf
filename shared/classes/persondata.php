<?php
use System\Models\Person;
use System\ACL\ACLHelper;
class PersonACL {
	const PRIVATE_ACCESS = 1;
	const FRIEND_ACCESS = 2;
	const FOF_ACCESS = 4;
	const EXT_ACCESS = 8;
	const PUBLIC_ACCESS = 16;
}
class PersonData extends \BaseObject {
	public $person_id;
	public $person_owner;
	private $_contacts;
	private $_person;
	private $_privs;
	private $_defaultPrivs;
	private $_cachedPrivs = array ();
	private $_currentPerson;
	private $_friends;
	function __construct(Person $p) {
		$privs = <<< EOP
{
	"fullname": {
		"privs":16
	},
	"first_name":{
		"privs":16
	},
	"middle_name":{
		"privs":1
	},
	"last_name":{
		"privs":1
	},
	"birth_date":{
		"privs":16,
		"format": "Y"
	},
	"friends" : {
		"privs":2,
	}
}
EOP;
		$this->_defaultPrivs = json_decode ( $privs );
		$this->_person = $p;
	}
	function __get($name) {
		$val = parent::__get ( $name );
		if (! $this->canview ( $name, $val )) {
			return null;
		}
		return $val;
	}
	private function getCurrentPerson() {
		if (! $this->_currentPerson) {
			$cuid = ACLHelper::getUserId ();
			$this->_currentPerson = $this->_person->getPersonByUser ( $cuid );
		}
		return $this->_currentPerson;
	}
	private function isCan($p) {
		if (! $this->_currentPerson) {
			$cuid = ACLHelper::getUserId ();
			$this->_currentPerson = $this->_person->getPersonByUser ( $cuid );
		}
		$ok = false;
		$isfriend= $this->isFriend();
		$isfriendOf = $this->isFriendOf();
		if ((PersonACL::PUBLIC_ACCESS & $p) === PersonACL::PUBLIC_ACCESS) {
			$ok = true;
		} elseif (((PersonACL::PRIVATE_ACCESS & $p) === PersonACL::PRIVATE_ACCESS) && $this->person_id === $this->_currentPerson->person_id) {
			$ok = true;
		} elseif ($isfriend && (((PersonACL::FRIEND_ACCESS & $p) === PersonACL::FRIEND_ACCESS))) {
			$ok = true;
		} elseif ($isfriendOf && (((PersonACL::FOF_ACCESS & $p) === PersonACL::FOF_ACCESS))) {
			$ok = true;
		}
		return $ok;
	}
	function canView($var, &$val) {
		$var = strtolower ( $var );
		$v = $this->getPersonPrivs ();
		if (! isset ( $v->$var )) {
			$v->$var = new stdClass();
			$v->$var->privs  = PersonACL::PRIVATE_ACCESS;
		}
		if (isset ( $this->_cachedPrivs [$var] )) {
			return $this->_cachedPrivs [$var];
		}
		$isOther = true;
		$p = ( int ) ($v->{$var}->privs ? $v->{$var}->privs : PersonACL::PRIVATE_ACCESS);
		$ok = $this->isCan ( $p );
		if ($ok) {
			switch ($var) {
				case 'birth_date' :
					if ($val && $isOther) {
						$d = new \CDate ( $val );
						$val = $d->format ( isset ( $v->{$var}->format ) ? $v->{$var}->format : 'Y' ).',<span>'.$d->diff(new \CDate())->format('%y Years').'</span>';
					}
					break;
			}
		}
		$this->_cachedPrivs [$var] = $ok;
		return $this->_cachedPrivs [$var];
	}
	private function getPersonPrivs() {
		if (! $this->_privs) {
			if ($this->person_owner < 0) {
				$this->_privs = $this->_defaultPrivs;
			} else {
				$f = \CGAF::getUserStorage ( $this->person_owner, false ) . $this->person_id . DS . 'privs.json';
				if (is_file ( $f )) {
					$this->_privs = json_decode ( file_get_contents ( $f, false ) );
				}
			}
		}
		return $this->_privs;
	}
	public function getFullName() {
		return sprintf ( '%s %s %s', $this->first_name, $this->middle_name, $this->last_name );
	}
	function getFriends() {
		if ($this->_friends === null) {
			$this->_friends = array ();
			$r=null;
			if ($this->canview ( 'friends', $r )) {
				$f = \CGAF::getUserStorage ( $this->person_owner, false ) . $this->person_id . DS . 'friends.json';
				if (is_file ( $f )) {
					
					ppd ( $f );
				}
			}
		}
		return $this->_friends;
	}
	function isFriend($uid=null) {
		$uid =$uid !==null  ? $uid : ACLHelper::getUserId();
		if ($uid=== $this->person_owner) {
			return true;
		}
		if  ($uid===-1) {
			return false;
		}
		$friends = $this->getFriends();
		ppd($friends);
	}
	function isFriendOf($uid=null) {
	$uid =$uid !==null  ? $uid : ACLHelper::getUserId();
		return true;
	}
	function getActivities() {
	}
	function getContacts() {
		if ($this->_contacts === null) {
			$f = \CGAF::getUserStorage ( $this->person_owner, false ) . $this->person_id . DS . 'contacts.json';
			if (is_file ( $f )) {
				$this->_contacts = array ();
				$c = json_decode ( file_get_contents ( $f ) );
				$isprivate = true;
				$isfriend = true;
				$cuid = ACLHelper::getUserId();
				$cperson = $this->getCurrentPerson();
				$isfriendOf = true;
				$ispublic = $cuid === - 1;
				if (( int ) $cperson->person_id !== $this->person_id) {
					$isfriend = $this->isFriend ( $cuid );
					$isfriendOf = $this->isFriendOf ( $cuid );
				}
				foreach ( $c as $v ) {
					$v->privs = ( int ) ($v->privs ? $v->privs : PersonACL::PRIVATE_ACCESS);
					if ($this->isCan ( $v->privs )) {
						$this->_contacts [] = $v;
					}
				}
			} else {
				$this->_contacts = false;
			}
		}
		return $this->_contacts;
	}	
}

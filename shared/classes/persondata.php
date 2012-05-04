<?php
use System\Exceptions\InvalidOperationException;

use System\Models\Person;
use System\ACL\ACLHelper;
use System\Documents\Image;



class PersonData extends \BaseObject {
	public $person_id;
	public $person_owner;
	private $_contacts;
	private $_person;
	private $_privs;
	private $_defaultPrivs;
	private $_cachedPrivs = array();
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
		"privs":2
	},
	"images" : {
		"privs" :2
	}
}
EOP;
		$this->_defaultPrivs = json_decode($privs);
		$this->_person = $p;
	}

	function __get($name) {
		$val = parent::__get($name);
		if (!$this->canview($name, $val)) {
			return null;
		}
		return $val;
	}

	private function getCurrentPerson() {
		if (!$this->_currentPerson) {
			$cuid = ACLHelper::getUserId();
			$this->_currentPerson = $this->_person->getPersonByUser($cuid);
			if (!$this->_currentPerson) {
				$dummy = new stdClass();
				$dummy->person_id=-1;
				$this->_currentPerson = $dummy;
			}
		}
		return $this->_currentPerson;
	}


	function getStorePath($p = null, $create = false) {
		return \CGAF::getInternalStorage('persons/' . $this->person_id . DS . $p . DS, false, $create);
	}

	private function getCachedImage($f, $size = 'full',$live=false) {
		if ($size === 'full') {
			return $f;
		}
		$fname = $this->getStorePath('.cache/images/', true) . hash('crc32', $f . $size) . \Utils::getFileExt($f);
		@unlink($fname);
		if (!is_file($fname)) {
			$img = new Image($f);
			$out = $img->resize($size, $fname);
		}
		return $fname;
	}

	/**
	 * @param string $name
	 * @param null $size
	 * @return null|string real path if found or null
	 */
	function getImage($name = null, $size = null,$live=false) {
		$a = null;

		$f = null;
		if ($name === null) {
			//TODO get from default image configuration
			$name = 'profile/default.png';
		}
		if ($this->canView('images', $a)){
			$f = $this->getStorePath('images') . $name;
		}
		if (!$f || !is_file($f)) {
			$f = CGAF_PATH . 'assets/images/anonymous.png';
		}

		if ($live) {
			//Handled by person controller
			return \URLHelper::add(APP_URL,'person/image/'.basename($name).'?id='.$this->person_id .'&size='.$size);
		}
		return $this->getCachedImage($f, $size,$live);
	}
	function isMe() {
		return $this->person_owner === ACLHelper::getUserId();
	}
	private function isCan($p) {

		$this->getCurrentPerson();
		if ($this->isMe()) {
			return true;
		}
		$ok = false;
		$isfriend = $this->isFriend();
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
		$var = strtolower($var);
		$v = $this->getPersonPrivs();
		if (!$v) ppd($var);
		if (!isset ($v->$var)) {

			$v->$var = new stdClass();
			$v->$var->privs = PersonACL::PRIVATE_ACCESS;
		}
		if (isset ($this->_cachedPrivs [$var])) {
			return $this->_cachedPrivs [$var];
		}
		$isOther = $this->isMe() === false;
		$p = ( int )($v->{$var}->privs ? $v->{$var}->privs : PersonACL::PRIVATE_ACCESS);
		$ok = $this->isCan($p);
		if ($ok) {

			switch ($var) {
				case 'birth_date' :
					if ($val) {
						$d = new \CDate ($val);
						$format =$this->isMe() ? $this->_person->getAppOwner()->getUserConfig('date.clientformat','m/d/Y') : (isset ($v->{$var}->format) ? $v->{$var}->format : 'Y');
						$val = $d->format($format) . ',<span>' . $d->diff(new \CDate())->format('%y Years') . '</span>';
					}
					break;
			}
		}
		$this->_cachedPrivs [$var] = $ok;
		return $this->_cachedPrivs [$var];
	}

	private function getPersonPrivs() {
		if (!$this->_privs) {
			if ($this->person_owner < 0) {
				$this->_privs = $this->_defaultPrivs;
			} else {
				$f = \CGAF::getUserStorage($this->person_owner, false) . $this->person_id . DS . 'privs.json';
				if (is_file($f)) {
					$this->_privs = json_decode(file_get_contents($f, false));
				}else{
					$this->_privs = $this->_defaultPrivs;
				}
			}
		}
		return $this->_privs;
	}

	public function getFullName() {
		return sprintf('%s %s %s', $this->first_name, $this->middle_name, $this->last_name);
	}

	function getFriends() {
		if ($this->_friends === null) {
			$this->_friends = array();
			$r = null;
			if ($this->canview('friends', $r)) {
				$f = \CGAF::getUserStorage($this->person_owner, false) . $this->person_id . DS . 'friends.json';
				if (is_file($f)) {

					ppd($f);
				}
			}
		}
		return $this->_friends;
	}

	function isFriend($uid = null) {
		$uid = $uid !== null ? $uid : ACLHelper::getUserId();
		if ($uid === $this->person_owner) {
			return true;
		}
		if ($uid === -1) {
			return false;
		}
		$friends = $this->getFriends();


	}

	function isFriendOf($uid = null) {
		$uid = $uid !== null ? $uid : ACLHelper::getUserId();
		return true;
	}

	function getActivities() {
	}

	function getContacts() {
		if ($this->_contacts === null) {
			$f = \CGAF::getUserStorage($this->person_owner, false) . $this->person_id . DS . 'contacts.json';
			if (is_file($f)) {
				$this->_contacts = array();
				$c = json_decode(file_get_contents($f));
				$isprivate = true;
				$isfriend = true;
				$cuid = ACLHelper::getUserId();
				$cperson = $this->getCurrentPerson();
				$isfriendOf = true;
				$ispublic = $cuid === -1;
				if (( int )$cperson->person_id !== $this->person_id) {
					$isfriend = $this->isFriend($cuid);
					$isfriendOf = $this->isFriendOf($cuid);
				}
				foreach ($c as $v) {
					$v->privs = ( int )($v->privs ? $v->privs : PersonACL::PRIVATE_ACCESS);
					if ($this->isCan($v->privs)) {
						$this->_contacts [] = $v;
					}
				}
			} else {
				$this->_contacts = false;
			}
		}
		return $this->_contacts;
	}
	function assign($var, $val = null) {
		$this->_internal =array();
		parent::assign($var,$val);
	}
	public static function getPrimaryCurrentUser() {
		$m= AppManager::getInstance()->getModel('person');
		$o = $m->getPersonByUser(ACLHelper::getUserId());
		return $o;
	}
	public static function getInfo($id) {
		//TODO Cache
		$m= AppManager::getInstance()->getModel('person')->clear();
		$m->where('person_id='.$m->quote($id));
		return $m->loadObject('\\PersonData');
	}
}

<?php
use System\Web\Utils\HTMLUtils;
use System\API\PublicApi;
use System\ACL\ACLHelper;
use System\Exceptions\SystemException;
use System\Applications\IApplication;

class CGAFUserInfo {
	private $_info;
	/**
	 * Enter description here ...
	 *
	 * @var System\MVC\Application
	 */
	private $_owner;
	private $_friend;
	private $_id;
	private $_wall;

	function __construct(IApplication $owner, $id) {
		$this->_id = $id;
		$this->_info = $owner->getModel('user')
			->getUserInfo($id);
		$this->_owner = $owner;
	}

	public static function parseCallback($callback, $descr) {
		if (!$callback) {
			return $callback;
		}
		// TODO parse by application
		switch ($callback) {
			case 'email' :
				$retval = '<a href="mailto:' . $descr . '" class="email"><img src="' . ASSET_URL . '/images/email.png"/><span>' . __('sendemail') . '</span></a>';
				break;
			case 'skype' :
				$retval = PublicApi::share('skype', 'onlinestatus', $descr);
				break;
			case 'ymsgrstatus' :
				$retval = PublicApi::share('yahoo', 'onlinestatus', $descr);
				break;
			default :
				$retval = null;
				if (CGAF_DEBUG) {
					throw new SystemException ('unhandled contact callback ' . $callback);
				}
		}
		if ($retval) {
			$retval = '<div class="contact ' . $callback . '">' . $retval . '</div>';
		}
		return $retval;
	}

	function __get($v) {
		if (!$this->_info) {
			return null;
		}
		return $this->_info->$v;
	}

	function setConfig($config, $val) {
		return $this->_owner->setUserConfig($config, $val, $this->_id);
	}

	function getConfig($config, $def = null) {
		return $this->_owner->getUserConfig($config, $def, $this->_id);
	}

	function get($access, $person = null) {
		$access = strtolower($access);
		$person = $person ? $person : $this->getPerson();
		// TODO generate while user register
		$def = array(
			'birth_date' => 'd/m',
			'email' => 'public'
		);
		// TODO Filter by current user
		$cfg = $this->getConfig('person.' . $person->person_id . '' . $access, isset ($def [$access]) ? $def [$access] : 'self');
		$allow = false;
		switch ($cfg) {
			case 'self' :
				$allow = $this->isCurrentUser();
				break;
			case 'public' :
				$allow = true;
				break;
			case 'friend' :
				$allow = $this->isFriendOf(ACLHelper::getUserId());
				break;
			default :
				$allow = true;
				break;
		}
		if ($allow) {
			if (!$person->$access) {
				return null;
			}
			switch ($access) {
				case 'birth_date' :
					$d = new \DateTime ($person->$access);
					return $d->format($cfg);
					break;
				default :
					break;
			}
			// ppd($person->$access);
		}
		return false;
	}

	function addFriend($id) {
		if (!$this->isFriendOf($id)) {
			$m = $this->_owner->getModel('friend');
			$m->to_person = $id;
			$m->connect_type = 1;
			// TODO should recheck for security
			if ($m->store()) {
				$this->_friend = null;
			}
		}
		return true;
	}

	private function getStatus($id = null) {
		$id = $id == null ? ACLHelper::getUserId() : $this->_id;
		if (!$this->_wall) {
			$m = $this->_owner->getModel('userwall')->clear();
			$this->_wall = $m->where('user_id=' . $m->quote($id))->orderby('date_publish desc')->loadAll();
		}
		return $this->_wall;
	}

	function getLastStatus() {
		$st = $this->getStatus();
		if (count($st)) {
			ppd($st);
		}
		return '';
	}

	function getUserInfoModules($type, $uid = null, $ajaxmode = true) {
		$uid = $uid === null ? $this->_id : $uid;
		$m = $this->_owner->getModel('usercontent');
		$m->setIncludeAppId(false);
		$m->reset();
		$m->where('app_id=' . $m->quote(\CGAF::APP_ID));
		$m->where('user_id=' . $uid);
		$m->where('position=' . $m->quote($type));
		$rows = $m->loadObjects();
		$m->setIncludeAppId(true);
		if ($rows) {
			$retval = '<div class="user-modules">';
			$retval .= '<ul>';
			foreach ($rows as $idx => $row) {
				$retval .= '<li>' . HTMLUtils::renderLink('#tab-' . $idx, __($row->content_title)) . '</li>';
			}
			$retval .= '</ul>';
			$retval .= $this->_owner->renderContents($rows, $type, array(
			                                                            'person' => $this->getPerson(),
			                                                            'uid' => $uid
			                                                       ), true);
			$retval .= '</div>';
			return $retval;
		}
		if ($uid !== -1) {
			return $this->getUserInfoModules($type, -1);
		}
		return '';
	}

	function isCurrentUser() {
		return $this->_id === ACLHelper::getUserId();
	}

	function getPerson() {
		static $person;
		if (!$person) {
			$m = $this->_owner->getModel("person");
			$person = $m->getPersonByUser($this->_id);
		}
		return $person;
	}

	function getFriendList($id = null) {
		$id = $id == null ? ACLHelper::getUserId() : $id;
		if (!$this->_friend) {
			$this->_friend = $this->_owner->getModel('friend')->loadFriend($id);
		}
		return $this->_friend;
	}

	function isFriendOf($id = null) {
		return array_key_exists($this->getFriendList(), $id);
	}
}

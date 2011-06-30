<?php
if (! defined("CGAF"))
	die("Restricted Access");

class DBAuthentificator implements IAuthentificator {
	/**
	 * Query Object
	 *
	 * @var TDBQuery
	 */
	protected $_q = null;
	private $_tbl = "users";

	/**
	 * Enter description here ...
	 * @param IDBAware $appOwner
	 * @param string $table
	 */
	function __construct(IDBAware $appOwner,$table = 'users') {
		parent::__construct($appOwner);
		$this->_tbl = $table;
		if ($appOwner->getConfig("app.internalAuthentification", false)) {
			$this->_q = new DBQuery($appOwner);
		} else { 
			$this->_q = new DBQuery(CGAF::getDBConnection());
		}
	}
	function encryptPassword($p) {
		return Utils::getCryptedPassword($p,null,'md5-hex');
	}
	function Authenticate($args = null) {
		$this->setLastError(null);
		if ($args == null) {
			$args = Request::gets(null,true);
		}

		$o = $this->_q->clear()->addTable($this->_tbl)
		->Where("user_name=" . $this->_q->quote($args ["username"]))
		->Where("user_password=".$this->_q->quote($this->encryptPassword($args["password"])))
		->Where("user_status>=1")
		->loadObject();

		if ($o !== null) {
			Session::set("__logonInfo", $o);
			$this->getAppOwer()->Log("Login", $o->user_id, true);
			return $o;
		}
		$this->setLastError("Invalid Username/password");
		return false;
	}
	function Logout() {
		return true;
	}

}
?>
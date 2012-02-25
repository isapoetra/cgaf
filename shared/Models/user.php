<?php
namespace System\Models;
use System\MVC\Models\ExtModel;
use System\Exceptions\InvalidOperationException;
use CGAF;
class User extends ExtModel {
	/**
	 * @FieldType int
	 * @FieldPrimary true
	 * @FieldArg NOT NULL PRIMARY KEY
	 */
	public $user_id;
	/**
	 * @FieldType varchar
	 * @FieldLength 150
	 */
	public $user_name;
	/**
	 * @FieldType varchar
	 * @FieldLength 150
	 */
	public $user_password;
	/**
	 * @FieldType integer
	 */
	public $person_id;
	/**
	 * @FieldType smallint
	 */
	public $user_status;
	/**
	 * @FieldType smallint
	 */
	public $user_state;
	/**
	 *
	 * @var unknown_type @FieldType varchar
	 *      @FieldLength 150
	 */
	public $activation_key;
	/**
	 *
	 * @var date @FieldType DATETIME
	 */
	public $registerDate;
	/**
	 *
	 * @var unknown_type @FieldType int
	 *      @FieldLength 11
	 */
	public $defaultrole;
	/**
	 *
	 * @var date @FieldType DATETIME
	 */
	public $last_access;
	/**
	 *
	 * @var string @FieldType varchar
	 *      @FieldLength 45
	 */
	public $last_ip;
	function __construct($connection = null) {
		parent::__construct ( CGAF::getDBConnection (), "users", "user_id", false, \CGAF::isInstalled () === false );
		if ($connection instanceof Application) {
			$this->setAppOwner ( $connection );
		}
	}
	function check($mode = null) {
		$mode = $this->getCheckMode ( $mode );
		if (! $this->activation_key) {
			$this->activation_key = \Utils::generateActivationKey ();
		}
		$auth = $this->getAppOwner ()->getAuthentificator ();
		if ($mode === 'update') {
			if ($this->user_password !== $this->_oldData->user_password) {
				$this->user_password = $auth->encryptPassword ( $this->user_password );
			}
		} else {
			$this->user_password = $auth->encryptPassword ( $this->user_password );
		}
		return parent::check ( $mode );
	}
	function getUserInfo($id) {
		$this->clear ( 'all' );
		$this->select ( 'vw.*' );
		$this->addTable ( 'vw_userinfo', 'vw', true );
		$this->where ( 'vw.user_state=1' );
		$this->where ( 'vw.user_id = ' . $this->quote ( $id ) );
		// ppd($this->getSQL());
		return $this->loadObject ();
	}
	function reset($mode = null, $id = null) {
		$this->setAlias ( 'u' );
		parent::clear ( 'all' );
		$this->select ( 'u.user_id,u.user_name,st.value status,su.value state,u.registerDate,ss.session_id,w.*' );
		$this->join ( 'vw_user_status', 'st', 'st.key=u.user_status', 'left', true );
		$this->join ( 'vw_user_state', 'su', 'su.key=u.user_state', 'left', true );
		$this->join ( 'vw_userinfo', 'w', 'w.user_id=u.user_id', 'inner', true );
		$this->join ( 'session', 'ss', 'u.user_id=ss.user_id', 'left' );
		return $this;
	}
	function getUserByEmail($email) {
		$this->setAlias ( 'u' );
		$this->clear ();
		$this->select ( 'w.*' );
		$this->join ( 'vw_userinfo', 'w', 'w.user_id=u.user_id', 'inner', true );
		$this->where ( 'w.email=' . $this->quote ( $email ) );
		return $this->loadObject ();
	}
	function delete() {
		$o = $this->load ();
		if (( int ) $o->user_status === 999) {
			throw new InvalidOperationException ( 'unable to delete internal user' );
		}
		if (( int ) $o->user_state === 0 || ( int ) $o->user_state === - 1) {
			return parent::delete ();
		}
		throw new InvalidOperationException ( 'invalid status for user state,delete user only valid when disabled or new data' );
	}
	protected function getGridColsWidth() {
		return array (
				'registerDate' => 250
		);
	}
	function loadByIdentify($id) {
		$this->setAlias ( 'u' );
		$this->clear ();
		// $this->select('w.*');
		// $this->join('vw_userinfo', 'w', 'w.user_id=u.user_id', 'inner',
		// true);
		$this->where ( 'u.user_name=' . $this->quote ( $id ) );
		return $this->loadObject ();
	}
	function loadListEmail() {
		$this->setAlias ( 'u' );
		$this->clear ();
		$this->select ( 'u.user_id as `key`,w.fullname as `value`,w.email as descr' );
		$this->join ( 'vw_userinfo', 'w', 'w.user_id=u.user_id', 'inner', true );
		$this->where ( 'w.email !=\'\'' );
		return $this->LoadAll ();
	}
}

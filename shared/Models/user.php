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
	 * @var string
	 */
	public $user_name;
	/**
	 * @FieldType varchar
	 * @FieldLength 150
	 * @var string
	 */
	public $user_password;
	/**
	 * @FieldType smallint
	 * @var int
	 */
	public $user_status;
	/**
	 * @FieldType smallint
	 * @var int
	 */
	public $user_state;
	/**
	 *
	 *@FieldType varchar
	 *@FieldLength 150
	 *@var string
	 */
	public $activation_key;
	/**
	 *
	 *  @FieldType DATETIME
	 *  @var \DateTime
	 */
	public $registerDate;
	/**
	 *
	 *@FieldType int
	 *@FieldLength 11
	 *@var string
	 */
	public $defaultrole;
	/**
	 *@FieldType DATETIME
	 *@var \DateTime
	 */
	public $last_access;
	/**
	 *@FieldLength 45
	 *@FieldType varchar
	 *@var string
	 */
	public $last_ip;
	/**
	 * @FieldType text
	 *
	 * @var int
	 */
	public $states;
	public $user_email;
	function __construct($connection = null) {
		parent::__construct ( CGAF::getDBConnection (), "users", "user_id", false, \CGAF::isInstalled () === false );
		if ($connection instanceof Application) {
			$this->setAppOwner ( $connection );
		}
	}
	function check($mode = null) {
		$mode = $this->getCheckMode ( $mode );
		if (! $this->activation_key && (int)$this->user_state===0) {
			$this->activation_key = \Utils::generateActivationKey ();
		}
		$auth = $this->getAppOwner ()->getAuthentificator ();
		if ($mode === self::MODE_UPDATE) {
			if ($this->user_password !== $this->_oldData->user_password) {
				$this->user_password = $auth->encryptPassword ( $this->user_password );
			}
		} else {
			if (!$this->user_email && \Utils::isEmail($this->user_name)) {
				$this->user_email = $this->user_name;
			}
			$this->user_password = $auth->encryptPassword ( $this->user_password );
		}
		return parent::check ( $mode );
	}
	function getUserInfo($id) {
		$this->clear ( 'all' );
		$this->select ( 'vw.*' );
		$this->addTable ( 'vw_userinfo', 'vw', true );
		if (!$this->getAppOwner()->getACL()->isInRole(\System\ACL\ACLHelper::DEV_GROUP)) {
			$this->where ( 'vw.user_state=1' );
		}
		$this->where ( 'vw.user_id = ' . $this->quote ( $id ) );
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
		$this->where ( 'w.user_email=' . $this->quote ( $email ) );
		return $this->loadObject ();
	}
	function delete($id = null) {
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
	function loadByExternalId($id,$ext) {
		$this->setAlias ( 'u' );
		$this->clear ();
		$this->join('user_external','ux','u.user_id=ux.userid')
		->Where('ux.extid='.$this->quote($id))
		->where('ux.exttype='.$this->quote($ext));
		return $this->loadObject();
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

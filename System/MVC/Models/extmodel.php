<?php
namespace System\MVC\Models;
use System\Exceptions\AccessDeniedException;
use System\MVC\Model;
use System\ACL\ACLHelper;
/**
 * Enter description here .
 * ..
 *
 * @author Iwan Sapoetra
 */
class ExtModel extends Model {
	/**
	 * @FieldType int
	 * @FieldDefaultValue #CURRENT_USER#
	 * @var int User Created Id
	 */
	public $user_created;
	/**
	 * @FieldType TIMESTAMP
	 * @FieldDefaultValue CURRENT_TIMESTAMP
	 *
	 * @var \DateTime Date Created
	 */
	public $date_created;
	public $user_modified;
	public $date_modified;
	/**
	 * @FieldDefaultValue 1
	 * @var smallint
	 */
	public $data_state = 1;
	protected $_directDelete = false;
	function check($mode = null) {
		$mode = $this->getCheckMode ( $mode );
		switch ($mode) {
			case self::MODE_INSERT :
				$this->user_created = ACLHelper::getUserId ();
				$this->user_modified = ACLHelper::getUserId ();
				$this->date_created = $this->getConnection ()->DateToDB ();
				$this->date_modified = $this->getConnection ()->DateToDB ();
				$this->data_state = 1;
				break;
		}
		return parent::check ( $mode );
	}
	function getRowCount($clean = true) {
		if ($clean) {
			$this->clear ();
		}
		$this->where ( 'data_state=1' );
		return parent::getRowCount ();
	}
	function undel($id = null) {
		if ($this->_directDelete) {
			$this->setLastError ( 'Direct delete mode active' );
			return false;
		}
		$id = $id ? $id : \Request::get ( 'id' );
		if ($id == '0') {
			throw new AccessDeniedException ();
		}
		$m = $this->load ( $id, true );
		if (( int ) $m->data_state !== 999) {
			$this->setLastError ( 'data already restored' );
			return false;
		}
		$m->update ( 'data_state', 1 );
		$m->exec ();
		return true;
	}
	function delete($id = null) {
		$id = $id ? $id : \Request::get ( 'id' );
		if ($id == '0')
			throw new AccessDeniedException ();
		if (! $this->_directDelete) {
			$this->clear ();
			$m = $this->load ( $id, true );
			if (( int ) $m->data_state === 999) {
				$this->setLastError ( 'data already deleted' );
				return false;
			}
			$m->update ( 'data_state', 999 );
			$m->exec ();
			return true;
		}
		return parent::delete ( $id );
	}
	function prepare($type = null) {
		switch ($type) {
			case self::MODE_INSERT :
				$this->insert ( 'user_created', ACLHelper::getUserId () );
				$this->insert ( 'user_modified', ACLHelper::getUserId () );
				$this->insert ( 'date_created', $this->getConnection ()->DateToDB () );
				$this->insert ( 'date_modified', $this->getConnection ()->DateToDB () );
				$this->insert ( 'data_state', 1 );
				break;
			case self::MODE_UPDATE :
				$this->Update ( 'date_modified', $this->getConnection ()->DateToDB () );
				$this->Update ( 'user_modified', ACLHelper::getUserId () );
				break;
		}
		return parent::prepare ();
	}
	function reset($mode = null, $id = null) {
		parent::reset ();
		$this->orderBy ( 'date_created desc' );
		return $this;
	}
}

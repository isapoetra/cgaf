<?php
namespace System\Controllers;
use System\Exceptions\SystemException;
use System\JSON\JSONResult;
use System\MVC\Controller;
use System\Exceptions\InvalidOperationException;
use System\DB\DBQuery;
use System\ACL\ACLHelper;
use Request;
use Response;
use CGAF;
use Utils;
class Person extends Controller {
	function isAllow($access = 'view') {
		switch ($access) {
			case 'activities':
			case 'friends' :
				return $this->getAppOwner()->isAuthentificated();
			case 'view' :
			case 'index' :
			case 'image' :
			case 'info' :
			case 'contacts' :
				$access = 'view';
		}
		return parent::isAllow ( $access );
	}
	function contacts($args = null) {
		$pi = null;
		if (is_array ( $args )) {
			// from rendercontents?
			$pi = isset ( $args ['row'] ) ? $args ['row'] : null;
		} elseif ($args !== null) {
			// from direct access ?
			$m = $this->getModel ();
			$m->Where ( 'person_id=' . $m->quote ( $args ) );
			$pi = $m->loadObject ( '\\PersonData' );
		}
		if (! ($pi instanceof \PersonData)) {
			return '';
		}
		return parent::render ( __FUNCTION__, array (
				'rows' => $pi->getContacts ()
		) );
	}
	function friends($args = null) {
		if (is_array ( $args )) {
			// from rendercontents?
			$pi = isset ( $args ['row'] ) ? $args ['row'] : null;
		} elseif ($args !== null) {
			// from direct access ?
			$m = $this->getModel ();
			$m->Where ( 'person_id=' . $m->quote ( $args ) );
			$pi = $m->loadObject ( '\\PersonData' );
		}
		if (! ($pi instanceof \PersonData)) {
			return '';
		}
		return parent::render ( __FUNCTION__, array (
				'rows' => $pi->getFriends ()
		) );
	}
	function activities($args=null) {
		if (is_array ( $args )) {
			// from rendercontents?
			$pi = isset ( $args ['row'] ) ? $args ['row'] : null;
		} elseif ($args !== null) {
			// from direct access ?
			$m = $this->getModel ();
			$m->Where ( 'person_id=' . $m->quote ( $args ) );
			$pi = $m->loadObject ( '\\PersonData' );
		}

		return parent::render ( __FUNCTION__, array (
				'rows' => $pi->getActivities ()
		) );
	}
	
	
	
	function info() {
		$id = \Request::get ( 'id' );
		$m = $this->getModel ();
		$m->where ( 'person_id=' . $m->quote ( $id ) );
		$o = $m->LoadObject ( '\\PersonData' );
		if (! $o) {
			throw new SystemException ( 'Invalid Person' );
		}
		return parent::render ( __FUNCTION__, array (
				'row' => $o
		) );
	}
	function Index() {
		return parent::render ();
	}
	function sp() {
		$m = $this->getModel ();
		$s = Request::get ( 'q', Request::get ( 'term' ) );
		$r = $m->search ( $s );
		if (! $r) {
			return array ();
		}
		$retval = array ();
		if (\Request::get ( 'autoc' )) {
			\Response::clearBuffer ();
			\Request::isJSONRequest ( true );
			foreach ( $r as $v ) {
				$retval [] = array (
						'id' => $v->person_id,
						'label' => $v->first_name . ' ' . $v->last_name,
						'value' => $v->person_id
				);
			}
			return $retval;
		}
		return $r;
	}
	function search($s, $config) {
		$s = $s ? $s : Request::get ( 'q', Request::get ( 'term' ) );
		if (! $s || strlen ( $s ) < 4) {
			throw new InvalidOperationException ( 'empty' );
		}
		$retval = array ();
		$q = new DBQuery ( CGAF::getDBConnection () );
		$q->addTable ( 'vw_userinfo', 'u', true )->where ( 'user_state>0' )->where ( 'user_id <> -1' )->where ( 'fullname like \'%' . $q->quote ( $s, false ) . '%\'' );
		$rows = $q->loadObjects ();
		foreach ( $rows as $row ) {
			$allow = $this->getAppOwner ()->getUserConfig ( 'allowSearchByOther', CGAF_DEBUG, $row->user_id );
			if ($allow) {
				$retval [] = $row;
			}
		}
		return parent::Render ( array (
				'_a' => 'searchResult'
		), array (
				'rows' => $retval,
				's' => $s
		) );
	}
	private function getImageFile($uid, $image, $w, $h) {
		$uid = ACLHelper::isAllowUID ( Request::get ( 'uid' ) );
		if ($uid === ACLHelper::PUBLIC_USER_ID) {
			$uid = 'public';
		}
		$f = Utils::getFileName ( $image, false );
		$ext = Utils::getFileExt ( $image );
		if ($w && $h) {
			$cpath = $this->getInternalPath ( 'image/.cache/' . $uid );
			$sfile [] = $cpath . $f . '_' . $w . '_' . $h . $ext;
		}
		$p = $this->getInternalPath ( 'image/' . $uid );
		$sfile [] = $p . $image;
		$found = null;
		foreach ( $sfile as $file ) {
			if (is_file ( $file )) {
				$found = $file;
				break;
			}
		}
		// TODO check for user allowed image
		return $found;
	}
	function image() {
		$uid = ACLHelper::isAllowUID ( Request::get ( 'id' ) );
		$img = $this->getImageFile ( $uid, Request::get ( 'image', 'default.png' ), Request::get ( 'w' ), Request::get ( 'h' ) );
		if (! $img) {
			$img = CGAF_PATH . 'assets/images/anonymous.png';
		}
		return \Streamer::Stream ( $img );
	}
	public function profile() {
		$uid = ACLHelper::isAllowUID ( Request::get ( 'id' ) );
		if ($uid === ACLHelper::PUBLIC_USER_ID) {
			return parent::render ( array (
					'_a' => 'publicprofile'
			) );
		}
		return parent::render ( null, array () );
	}
	public function addfriend() {
		$id = Request::get ( 'id' );
		$info = $this->getAppOwner ()->getUserInfo ( $id );
		if ($info->getConfig ( 'addasfriend', CGAF_CONFIG )) {
			if ($this->getAppOwner ()->isValidToken ()) {
				$r = $info->addFriend ( $id );
				return new JSONResult ( $r, null, null, array (
						'closeoverlay' => true,
						'content' => __ ( 'person.addfriend.waitconfirm' )
				) );
			} else {
				return parent::render ( null, array (
						'row' => $info
				) );
			}
		} else {
			throw new SystemException ( 'person.addfriend.reject' );
		}
	}
	public function getActionAlias($action) {
		switch (strtolower ( $action )) {
			case 'af' :
				return 'addfriend';
		}
		return parent::getActionAlias ( $action );
	}
}

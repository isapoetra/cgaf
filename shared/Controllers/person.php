<?php
namespace System\Controllers;
use System\JSON\JSONResponse;

use System\Exceptions\UnimplementedException;

use System\Exceptions\AccessDeniedException;

use \System\Documents\Image;
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
			case 'view' :
			case 'index' :
			case 'image' :
			case 'info':
			case 'detail':
				return true;
			case 'myperson':
			case 'activities':
			case 'friends' :
			case 'aed':
			case 'add':
			case 'edit':
			case 'setprimaryperson':
			case 'store':
			case ACLHelper::ACCESS_MANAGE:
			case ACLHelper::ACCESS_WRITE:
			case ACLHelper::ACCESS_UPDATE:
				return $this->getAppOwner()->isAuthentificated();
			case 'info' :
			case 'contacts' :
				$access = 'view';
		}
		return parent::isAllow($access);
	}
	function edit($row=null) {
		$id = \Request::get('id');
		$p = $this->getPerson($id);
		if ($p && $p->isMe()) {
			return parent::renderView('aed',array('row'=>$p));
		}
		throw new AccessDeniedException();
	}
	private function getPerson($id) {
		$m = $this->getModel()->clear();
		$m->Where('person_id=' . $m->quote($id));
		return $m->loadObject('\\PersonData');
	}
	function store() {
		throw new UnimplementedException();
	}
	function aed($row=null,$action='aed',$args=null) {
		$id =\Request::get('id');

		$args =$args ? $args : array();
		$args['errors']= isset($args['errors']) ? $args['errors'] : '';
		if (!$id) {
			$p = $this->getModel()->clear('field')->select('count(*)','pt',true)
			->where('person_owner='.ACLHelper::getUserId())
			->loadObject();
			if ($p->pt > (int) $this->getAppOwner()->getConfig('app.person.maximum',2)) {
				if (!ACLHelper::isInrole(ACLHelper::ADMINS_GROUP)) {
					throw new InvalidOperationException('Maximum person Reached');
				}
			}
		}
		if ($this->getAppOwner()->isValidToken()) {
			$m=$this->getModel()->clear();
			$m->bind(\Request::gets('p'));
			$m->person_id=$id;
			if (!$pid=$m->store(false)) {
				$args['errors'] =$m->getLastError();
				if (\Request::isJSONRequest()) {
					return new JSONResult(false, implode(',',$args['errors']));
				}
			}else{
				return \Response::Redirect(\URLHelper::add(APP_URL,'person/detail/?id='.$id));
			}
		}
		return parent::aed($row,$action,$args);
	}
	function contacts($args = null) {
		$pi = null;
		if (is_array($args)) {
			// from rendercontents?
			$pi = isset ($args ['row']) ? $args ['row'] : null;
		} elseif ($args !== null) {
			// from direct access ?
			$m = $this->getModel();
			$m->Where('person_id=' . $m->quote($args));
			$pi = $m->loadObject('\\PersonData');
		}
		if (!($pi instanceof \PersonData)) {
			return '';
		}
		return parent::render(__FUNCTION__, array(
				'rows' => $pi->getContacts()
		));
	}

	function friends($args = null) {
		if (is_array($args)) {
			// from rendercontents?
			$pi = isset ($args ['row']) ? $args ['row'] : null;
		} elseif ($args !== null) {
			// from direct access ?
			$m = $this->getModel();
			$m->Where('person_id=' . $m->quote($args));
			$pi = $m->loadObject('\\PersonData');
		}
		if (!($pi instanceof \PersonData)) {
			return '';
		}
		return parent::render(__FUNCTION__, array(
				'rows' => $pi->getFriends()
		));
	}

	function activities($args = null) {
		if (is_array($args)) {
			// from rendercontents?
			$pi = isset ($args ['row']) ? $args ['row'] : null;
		} elseif ($args !== null) {
			// from direct access ?
			$m = $this->getModel();
			$m->Where('person_id=' . $m->quote($args));
			$pi = $m->loadObject('\\PersonData');
		}

		return parent::render(__FUNCTION__, array(
				'rows' => $pi->getActivities()
		));
	}



	function detail($args = null, $return = null) {
		$id = ($args && isset($args['id']) ? $args['id'] :\Request::get('id'));
		if (!$id) {
			$o=$this->getModel()
			->getPersonByUser(ACLHelper::getUserId());
		}else{
			$m = $this->getModel();
			$m->where('person_id=' . $m->quote($id));
			$o = $m->LoadObject('\\PersonData');
		}
		if (!$o || !$o->person_id) {
			throw new SystemException ('Invalid Person');
		}
		return parent::render(__FUNCTION__, array(
				'row' => $o
		));
	}

	function Index() {
		return parent::render();
	}

	function sp() {
		$m = $this->getModel();
		$s = Request::get('q', Request::get('term'));
		$r = $m->search($s);
		if (!$r) {
			return array();
		}
		$retval = array();
		if (\Request::get('autoc')) {
			\Response::clearBuffer();
			\Request::isJSONRequest(true);
			foreach ($r as $v) {
				$retval [] = array(
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
		$s = $s ? $s : Request::get('q', Request::get('term'));
		if (!$s || strlen($s) < 4) {
			throw new InvalidOperationException ('empty');
		}
		$retval = array();
		$q = new DBQuery (CGAF::getDBConnection());
		$q->addTable('vw_userinfo', 'u', true)
		->where('user_state>0')->where('user_id <> -1')
		->where('fullname like \'%' . $q->quote($s, false) . '%\'');
		$rows = $q->loadObjects();
		foreach ($rows as $row) {
			$allow = $this->getAppOwner()->getUserConfig('allowSearchByOther', CGAF_DEBUG, $row->user_id);
			if ($allow) {
				$retval [] = $row;
			}
		}
		return parent::Render(array(
				'_a' => 'searchResult'
		), array(
				'rows' => $retval,
				's' => $s
		));
	}

	function image() {
		$uid = Request::get('id');
		$m = $this->getModel();
		$m->Where('person_id=' . $m->quote($uid));
		/**
		 * @var $pi \PersonData
		 */
		$pi = $m->loadObject('\\PersonData');
		$img = $pi->getImage('profile/'.basename($_REQUEST['__url']),\Request::get('size'));
		return \Streamer::Stream($img);
	}

	public function profile() {
		$uid = ACLHelper::isAllowUID(Request::get('id'));
		if ($uid === ACLHelper::PUBLIC_USER_ID) {
			return parent::render(array(
					'_a' => 'publicprofile'
			));
		}
		return parent::render(null, array());
	}

	public function addfriend() {
		$id = Request::get('id');
		$info = $this->getAppOwner()->getUserInfo($id);
		if ($info->getConfig('addasfriend', CGAF_CONFIG)) {
			if ($this->getAppOwner()->isValidToken()) {
				$r = $info->addFriend($id);
				return new JSONResult ($r, null, null, array(
						'closeoverlay' => true,
						'content' => __('person.addfriend.waitconfirm')
				));
			} else {
				return parent::render(null, array(
						'row' => $info
				));
			}
		} else {
			throw new SystemException ('person.addfriend.reject');
		}
	}
	public function myperson($configs) {
		$configs = $configs ? $configs : array();
		$rows= $this->getModel()->where('person_owner=' . ACLHelper::getUserId())->loadObjects();
		$params=array_merge($configs,array('rows'=>$rows));
		return parent::renderView(__FUNCTION__,$params);

	}
	function setprimaryperson() {
		$id=(int)\Request::get('id');
		$cp = $this->getModel()
		->where('person_owner=' . ACLHelper::getUserId())
		->where('person_id='.$id)
		->loadObject();
		if ($cp->isMe()) {
			if (!$this->getAppOwner()->isValidToken()) {
				return parent::renderView('confirmprimary');
			}

			$r = $this->getModel()->clear()

			->Update('isprimary', false)
			->Where('person_owner=' . ACLHelper::getUserId())
			->exec();
			$r = $this->getModel()->clear()
			->Update('isprimary', true)
			->Where('person_owner=' . ACLHelper::getUserId())
			->Where('person_id='.$id)
			->exec();
			\Response::Redirect(\URLHelper::add(APP_URL,'/person/detail/?id='.$id));
		}else {
			//TODO Admin Only
		}


	}
	public function getActionAlias($action) {
		switch (strtolower($action)) {
			case 'p':
				return 'setprimaryperson';
			case 'af' :
				return 'addfriend';
		}
		return parent::getActionAlias($action);
	}
}

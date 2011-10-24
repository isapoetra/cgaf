<?php
namespace System\Controllers;
use System\Exceptions\SystemException;
use System\DB\DBUtil;
use System\MVC\Controller;
class VoteController extends Controller {
	function isAllow($access = 'view') {
		switch ($access) {
		case 'detail':
			return true;
		case 'view':
		case 'index':
			return true;
			break;
		case 'vote':
			return $this->getAppOwner()->isAuthentificated();
			break;
		}
		return parent::isAllow($access);
	}
	function Initialize() {
		if (parent::initialize()) {
			$this->setModel('vote');
			return true;
		}
		return false;
	}
	function renderVote($id, $msg = null, $args = array()) {
		$row = $this->getModel()->clear()->load($id, true);
		if (!$row) {
			throw new SystemException(__('vote.invalidid', 'Invalid Voting id' . $id));
		}
		$items = $this->getModel('vote_items')->where('vote_id=' . $row->vote_id)->loadObjects();
		if ($row->vote_status && !$id) {
			return parent::render('vote', array(
					'msg' => $msg,
					'items' => $items,
					'row' => $row));
		}
		$row->vote_title = $row->getLocalizeValue();
		return $this->render('voteresult', array(
						'row' => $row,
						'items' => $items,
						'msg' => $msg));
	}
	function vote() {
		$appOwner = $this->getAppOwner();
		$id = \Request::get('id');
		$model = $this->getModel();
		$row = $model->load($id);
		if ($row && $appOwner->isValidToken() && \Request::get('vote') !== null) {
			$cid = 'vote.' . $row->vote_type . '.' . $row->vote_id;
			$msg = null;
			$config = $appOwner->getUserConfig($cid);
			if (!$config) {
				$conn = $model->getConnection();
				$conn->exec('update #__vote_items set vote_result=vote_result+1 where vote_id=' . $row->vote_id . ' and vote_item_id=' . \Request::get('vote'));
				$appOwner->setUserConfig($cid, 1);
			} else {
				$msg = __('vote.duplicate');
			}
		}
		return $this->renderVote($id, $msg);
	}
	function Index() {
		$id = (int) \Request::get('id');
		$model = $this->getModel();
		$row = $model->load($id);
		if ($row) {
			$items = $this->getModel('vote_items')->where('vote_id=' . $id)->loadObjects();
			return parent::render('vote', array(
					'items' => $items,
					'row' => $row));
		}
	}
}

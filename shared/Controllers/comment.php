<?php
namespace System\Controllers;
use System\ACL\ACLHelper;
use System\JSON\JSONResult;
use System\Exceptions\SystemException;
use System\JSON\JSONResponse;
use System\Exceptions\InvalidOperationException;
use System\Web\Utils\HTMLUtils;
use System\MVC\Controller;
class CommentController extends Controller {
	function isAllow($access = 'view') {
		switch ($access) {
		case 'index':
		case 'view':
		case 'comment':
			return true;
			break;
		case 'detail':
		case 'like':
		case 'reply':
			return $this->getAppOwner()->isAuthentificated();
		default:
			;
			break;
		}
		return parent::isAllow($access);
	}
	function Initialize() {
		if (parent::Initialize()) {
			$this->setModel('comment');
			return true;
		}
		return false;
	}
	function reply() {
		$id = \Request::get('comment_id');
		if ($id === null) {
			throw new InvalidOperationException('invalid comment id');
		}
		if ($this->getAppOwner()->isValidToken()) {
			if (!\Request::get('comment_descr')) {
				throw new InvalidOperationException('invalid comment descr');
			}
			$o = $this->getModel()->load($id, true);
			if (!$o) {
				throw new InvalidOperationException('invalid comment id');
			}
			$o->comment_id = null;
			$o->comment_parent = $id;
			$o->comment_descr = \Request::get('comment_descr');
			if ($o->store()) {
				$this->getAppOwner()->resetToken();
				return new JSONResult(0, __('data.saved'));
			}
			throw new SystemException($o->lastError());
		} elseif (\REquest::get('__token')) {
			throw new InvalidOperationException('invalid Token');
		}
		return parent::renderView(__FUNCTION__, array(
				'comment_id' => $id));
	}
	function renderComment($rows) {
		if (!$rows) {
			return null;
		}
		$retval = '<ul class="comment-list">';
		foreach ($rows as $c) {
			$retval .= $this->renderSingle($c);
		}
		$retval .= '</ul>';
		return $retval;
	}
	function detail() {
		$id = \Request::get('comment_id');
		$m = $this->getModel()->load($id, true);
		if ((int) $m->comment_status !== 1 && !$this->isAllow('manage')) {
			throw new SystemException('invalid comment id');
		}
		return $this->renderSingle($m);
	}
	function reject() {
		$id = \Request::get('id');
		$m = $this->getModel()->delete($id);
		if ($m->store(false)) {
			\Response::Redirect(\URLHelper::add(APP_URL, 'comment/detail/?comment_id=' . $id));
		} else {
			throw new SystemException($m->lastError());
		}
	}
	function approve() {
		$id = \Request::get('id');
		$m = $this->getModel()->load($id, true);
		$m->comment_status = 1;
		if ($m->store(false)) {
			\Response::Redirect(\URLHelper::add(APP_URL, 'comment/detail/?comment_id=' . $id));
		} else {
			throw new SystemException($m->lastError());
		}
	}
	private function renderSingle($c) {
		$pp = null;
		$u = $this->getController('user');
		if ($u) {
			$pp = $u->getPublicProfile($c->user_created);
		}
		$class = '';
		if ($c->data_state == '999') {
			$class = 'deleted';
		}
		$retval = '<li class="item ' . $class . '">';
		$retval .= '<div class="item-container">';
		$img = $pp && $pp->user_image ? $pp->user_image : '/assets/images/anonymous.png';
		$retval .= '<img src="' . $img . '" class="profile"/>';
		$retval .= '<div>';
		if ($pp) {
			$retval .= '<a href="' . \URLHelper::add(APP_URL, 'user/profile/', 'id=' . $pp->user_id) . '">' . $pp->fullname . '</a>';
		} else {
			$retval .= '<span class="">' . __('anonymous') . '</span>';
		}
		$retval .= '<div class="comment-descr">' . $c->comment_descr . '</div>';
		$lc = $this->getController('like');
		$lcount = $lc->getCount('comment', $c->comment_id);
		$actions = array();
		if ($lcount) {
			$actions[] = ___('like.count', $lcount);
		}
		if ($c->data_state !== '999') {
			if ($this->isAllow('reply')) {
				$actions[] = HTMLUtils::renderLink(\URLHelper::add(APP_URL, 'comment/reply', 'comment_id=' . $c->comment_id), __('Reply'), array(
						'tag' => $c->comment_id,
						'class' => 'comment-reply-button'), 'icons/reply.png');
			}
			if ($this->isAllow('like')) {
				$actions[] = '<a href="' . \URLHelper::add(APP_URL, 'comment/like', 'id=' . $c->comment_id) . '">' . __('Like') . '</a>';
			}
		}
		if ($this->isAllow('manage')) {
			if ($c->data_state == '999') {
				$actions[] = '<a href="' . \URLHelper::add(APP_URL, 'comment/undel', 'id=' . $c->comment_id) . '">' . __('Restore') . '</a>';
			} else {
				$actions[] = '<a href="' . \URLHelper::add(APP_URL, 'comment/del', 'id=' . $c->comment_id) . '">' . __('Delete') . '</a>';
			}
			if ($c->data_state !== '999') {
				if ($c->comment_status !== '1') {
					$actions[] = '<a href="' . \URLHelper::add(APP_URL, 'comment/approve', 'id=' . $c->comment_id) . '">' . __('Approve') . '</a>';
				} else {
					$actions[] = '<a href="' . \URLHelper::add(APP_URL, 'comment/reject', 'id=' . $c->comment_id) . '">' . __('Reject') . '</a>';
				}
			}
		}
		$retval .= '</div>';
		//TODO move to controller?
		$retval .= '<div class="comment-action">';
		$retval .= implode(' · ', $actions);
		$retval .= '</div>';
		$retval .= '</div>';
		if ($c->childs) {
			$retval .= '<ul class="comment-list">';
			foreach ($c->childs as $cc) {
				$retval .= $this->renderSingle($cc);
			}
			$retval .= '</ul>';
		}
		$retval .= '</li>';
		return $retval;
	}
	function comment($args = null) {
		$args = $args ? $args : array();
		if (!isset($args['type'])) {
			return null;
		}
		if (!isset($args['add'])) {
			$args['add'] = $this->getAppOwner()->getConfig('comment.' . $args['type'] . 'add', $this->getAppOwner()->isAuthentificated());
		}
		$this->getModel()->recent($args['type'], @$args['item']);
		$rows = $this->getModel()->loadChild($args['type'], @$args['item'], 0, \Request::get('__p', -1), \Request::get('__rpp', 10));
		$args['total'] = count($rows);
		/*$args['comments'] = $rows;*/
		$args['appid'] = $this->getAppOwner()->getAppId();
		$args['commentList'] = $this->renderComment($rows);
		return $this->render(__FUNCTION__, $args);
	}
}

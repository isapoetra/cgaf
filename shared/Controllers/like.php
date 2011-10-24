<?php
namespace System\Controllers;
use System\JSON\JSONResult;
use System\MVC\Controller;
class LikeController extends Controller {
	function __construct($appOwner) {
		parent::__construct($appOwner, 'like');
	}
	function isAllow($access = 'view') {
		switch ($access) {
		case 'view':
			return true;
			break;
		case 'like':
			if (CGAF_DEBUG) {
				return true;
			}
			break;
		}
		return parent::isAllow($access);
	}
	function Initialize() {
		if (parent::Initialize()) {
			$this->setModel('like');
			return true;
		}
	}
	function getCount($type, $item, $appId = null) {
		$appId = $appId ? $appId : \AppManager::getInstance()->getAppId();
		return $this->getModel()->getCountFor($type, $item, $appId);
	}
	function like($type, $item, $app) {
		$m = $this->getModel()->clear();
		//todo track for user
		$m->where('like_type=' . $m->quote($type));
		$m->where('like_item=' . $m->quote($item));
		$m->where('app_id=' . $m->quote($app));
		$o = $m->loadObject();
		if (!$o) {
			$m->clear();
			$m->insert('like_type', $type);
			$m->insert('like_item', $item);
			$m->insert('app_id', $app);
			$m->insert('count', 1);
		} else {
			$m->clear();
			$m->where('like_type=' . $m->quote($type));
			$m->where('like_item=' . $m->quote($item));
			$m->where('app_id=' . $m->quote($app));
			$m->update('count', '`count`+1', '=', true);
		}
		if ($m->exec()) {
			if (\Request::isJSONRequest()) {
				return __('like.thanks');
			}
			return parent::render(__FUNCTION__);
		}
	}
}

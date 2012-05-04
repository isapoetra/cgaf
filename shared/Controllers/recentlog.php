<?php
namespace System\Controllers;
use System\ACL\ACLHelper;

use System\MVC\Controller;
class RecentLogController extends Controller {
	function __construct($appOwner) {
		parent::__construct($appOwner, 'recentlog');
	}
	function Initialize() {
		if (parent::Initialize()) {
			$this->setModel('recentlog');
			return true;
		}
		return false;
	}
	function initAction($action, &$params) {
		parent::initAction($action, $params);
		switch ($action) {
		case 'index':
			$m = $this->getModel();
			$m->reset();
			try {
				$params['rows'] = $m->reset()->loadObjects();
			} catch (\Exception $e) {
				ppd($e);
			}
			break;
		default:
			;
			break;
		}
	}
	function isAllow($access = 'view') {
		switch ($access) {
		case 'index':
		case 'view':
			return true;
			break;
		default:
			;
			break;
		}
		return ACLHelper::isInrole(ACLHelper::DEV_GROUP);
	}
}

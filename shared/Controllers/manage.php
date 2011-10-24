<?php
namespace System\Controllers;
use System\MVC\Controller;
use System\ACL\ACLHelper;
class ManageController extends Controller {
	function isAllow($access = 'view') {
		return ACLHelper::isInrole(ACLHelper::ADMINS_GROUP) || ACLHelper::isInrole(ACLHelper::OWNER_GROUP);
	}
	function Index() {
		return parent::renderMenu('manage', null, true);
	}
}

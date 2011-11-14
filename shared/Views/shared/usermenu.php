<?php
use \System\Web\Utils\HTMLUtils;
$id = \System\ACL\ACLHelper::getUserId();
$appOwner = $this->getAppOwner();
$items = $appOwner->getMenuItems('user-menu', 0, null, true, true);
$replacer = array();
$attr = array(
		'id' => 'user_menu',
		'class' => 'user-menu');
if ($appOwner->isAuthentificated()) {
	$auth = $appOwner->getAuthInfo();
	$pi = $appOwner->getModel('person')->getPersonByUser($auth->getUserId());
	if ($pi) {
		$replacer['FullName'] = $pi->FullName;
	} else {
		$pi = $auth->getUserInfo();
		$replacer['FullName'] = $pi->user_name;
	}
} else {
	$replacer['FullName'] = '';
}
echo HTMLUtils::renderLinks($items, $attr, $replacer);
?>
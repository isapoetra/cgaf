<?php
use \System\Web\Utils\HTMLUtils;
use System\Web\UI\Controls\Menu;
if (!\CGAF::isInstalled()) {
	return;
}
$menu = new Menu();

$id = \System\ACL\ACLHelper::getUserId();
$appOwner = $this->getAppOwner();
$items = $appOwner->getMenuItems('user-menu', 0, null, true, true);
$menu->addChild($items);
$menu->setId('user_menu');
$menu->setClass('nav nav-pills user-menu');
//$menu->addStyle('float','right');
$replacer = array();

if ($appOwner->isAuthentificated()) {
	$auth = $appOwner->getAuthInfo();
	$pi = $appOwner->getModel('person')->getPersonByUser($auth->getUserId());		
	if ($pi && $pi->person_id) {
		$replacer['FullName'] = $pi->FullName;
	} else {
		$pi = $auth->getUserInfo();
		
		$replacer['FullName'] = $pi->user_name;
	}
	if (!$replacer['FullName']) {
		$replacer['FullName'] ='My';
	}
} else {
	$replacer['FullName'] = '';
}
$menu->setReplacer($replacer);
//echo HTMLUtils::renderLinks($items, $attr, $replacer);
echo '<div class="nav-collapse" id="user-menu-container">';
echo $menu->render(true);
echo '</div>';
return;
?>
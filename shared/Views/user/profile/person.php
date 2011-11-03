<?php
/**
 *
 * Enter description here ...
 * @var \UserInfo
 */
$script = <<< EOT
$('.user-modules').tabs();
EOT;
$appOwner->addClientScript($script);
$userInfo = $userInfo ? $userInfo : new \UserInfo($this->getAppOwner(), \System\ACL\ACLHelper::getUserId());
echo '<div>';
echo '<span class="uinfo">' . sprintf('%s %s %s', $personInfo->first_name, $personInfo->middle_name, $personInfo->last_name) . '</span>';
echo $userInfo->getLastStatus();
function renderInfo(\UserInfo $userInfo, $info) {
	$retval = '<div class="info-item ' . $info . '>';
	$retval .= '<span class="title">' . __('user.info.' . $info, $info) . '</span>';
	$retval .= '<span class="d">' . $userInfo->get($info) . '</span>';
	$retval .= '<div>';
	return $retval;
}
echo renderInfo($userInfo, 'email');
echo renderInfo($userInfo, 'birth_date');
echo $userInfo->getUserInfoModules('detail');
echo '</div>';

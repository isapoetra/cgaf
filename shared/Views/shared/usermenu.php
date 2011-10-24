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
	/*$script = <<< EOT
	$('#user_menu [popup]').click(function(e){
	    e.preventDefault();
	    var me = $(this);
	    me.parent().toggleClass('pop');
	    me.find('span:first').toggleClass('selected');
	    var el = $('#'+me.attr('popup'));
	    el.toggle();
	});
	EOT;
	$appOwner->addClientScript($script);*/
	$auth = $appOwner->getAuthInfo();
	$pi = $appOwner->getModel('person')->getPersonByUser($auth->getUserId());
	if ($pi) {
		$replacer['FullName'] = $pi->FullName;
	} else {
		$pi = $auth->getUserInfo();
		$replacer['FullName'] = $pi->user_name;
	}
}
echo HTMLUtils::renderLinks($items, $attr, $replacer);
return;
?>
<ul id='user_menu' class="user-menu">


<?php
if (!$appOwner->isAuthentificated()) {
	echo '<li>';
	echo HTMLUtils::renderLink(BASE_URL . 'auth', __('auth.login.title', 'Login'), null, 'login.gif');
	echo '</li><li>';
	echo HTMLUtils::renderLink(BASE_URL . 'user/register', __('user.register', 'Join'), null);
	echo '</li>';
} else {
	$appOwner->addClientAsset('user.css');
	echo '<li class="dropdown">';
	echo HTMLUtils::renderLink(BASE_URL . 'user/profile/', $pi->FullName, array(
			'id' => 'user-profile-link',
			'class' => 'dropdown-toggle',
			'popup' => 'user-profile-popup'));
	echo $appOwner->renderMenu('user-popup', false, null, 'dropdown-menu', false);
	echo '</li>';
}
echo '<li>';
echo '</li>';
									 ?>
</ul>

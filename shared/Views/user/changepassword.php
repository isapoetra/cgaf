<?php
echo HTMLUtils::beginForm(BASE_URL . '/user/changepassword', false);
if (!$adminmode) {
	echo HTMLUtils::renderPassword('Enter Old Password', 'oldpassword', '', array('class' => 'required'), true);
}
echo HTMLUtils::renderPassword('Enter New Password', 'password', '', array('class' => 'required'), true) . '<br/>';
echo HTMLUtils::renderPassword('Confirm Password', 'confirmpassword', '', array('class' => 'required'), true);
if (!$adminmode) {
	echo HTMLUtils::renderCaptcha();
}
echo HTMLUtils::endForm(true, true, true);
?>

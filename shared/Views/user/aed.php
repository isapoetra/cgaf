<?php
if (!defined("CGAF"))
	die("Restricted Access");
use System\Web\Utils\HTMLUtils;
use System\ACL\ACLHelper;
use System\MVC\MVCHelper;
$backAction = isset($backAction) ? $backAction : null;
if ($backAction) {
	echo "<div><a href=\"$backAction\">" . __("Back") . "</div>";
}
if ((int) $row->user_state !== 999) {
	echo HTMLUtils::beginForm(BASE_URL . "/user/store/");
	echo HTMLUtils::renderFormField(__("user.user_id", "user_id"), "user_id", $row->user_id, null, true, 'hidden');
	echo HTMLUtils::renderFormField(__("user.user_name", "user_name"), "user_name", $row->user_name, array(
			'class' => 'required'), true);
	echo HTMLUtils::renderFormField(__("user.user_password", "user_password"), "user_password", $row->user_password, array(
			'class' => 'password required'), true);
	//echo HTMLUtils::renderSelect(__("user.user_status", "user_status"), "user_status", MVCHelper::lookup('user_status', \CGAF::APP_ID), $row->user_status, false);
	if (ACLHelper::isInrole(ACLHelper::ADMINS_GROUP)) {
		echo HTMLUtils::renderSelect(__("user.user_state", "user_state"), "user_state", MVCHelper::lookup('user_state', \CGAF::APP_ID), $row->user_state, false);
	}
	echo HTMLUtils::endForm(true, true, true);
} else {
	echo HTMLUtils::renderError('Cannot Edit internal User');
}
?>
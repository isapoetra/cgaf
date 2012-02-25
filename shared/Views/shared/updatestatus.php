<?php
use System\MVC\MVCHelper;
use System\Web\Utils\HTMLUtils;
use System\DB\DBUtil;
?>
<div style="width: 400px; height: 200px; overflow: auto">
<?php
$lookup = isset ( $lookup ) ? $lookup : DBUtil::lookup ( 'default_status' );
$selected = isset ( $selected ) ? $selected : null;
$needMessage = isset ( $needMessage ) ? $needMessage : false;
$route = isset ( $route ) ? $route : MVCHelper::getRoute ();
$formAction = (isset ( $formAction ) ? $formAction : BASE_URL . '/' . $this->getController ()->getRouteName () . '/' . $route ['_a'] . '/') . '?id=' . Request::get ( 'id' );
$formattr = isset ( $formattr ) ? $formattr : null;
$formmultipart = isset ( $formmultipart ) ? $formmultipart : true;
$formshowmessage = isset ( $formshowmessage ) ? $formshowmessage : true;
$formmessage = isset ( $formmessage ) ? $formmessage : null;
$formattr = isset ( $formattr ) ? $formattr : null;
if (isset ( $formConfig )) {
	extract ( $formConfig );
}
echo HTMLUtils::beginForm ( $formAction, $formmultipart, $formshowmessage, $formmessage, $formattr );
echo HTMLUtils::renderRadioGroups ( null, 'value', $lookup, $selected );
if ($needMessage) {
	echo HTMLUtils::renderTextArea ( 'Message', 'message' );
}
echo HTMLUtils::endForm ( count ( $lookup ) > 0, true );
?>
</div>

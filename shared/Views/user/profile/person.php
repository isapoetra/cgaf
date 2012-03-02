<?php
$userInfo = $userInfo ? $userInfo : new \UserInfo ( $this->getAppOwner (), \System\ACL\ACLHelper::getUserId () );
echo '<section>';
echo '<h3>' . sprintf ( '%s %s %s', $personInfo->first_name, $personInfo->middle_name, $personInfo->last_name ) . '</h3>';
echo $userInfo->getLastStatus ();
function renderInfo(\UserInfo $userInfo, $info) {
	$retval = '<div class="row show-grid">';
	$retval .= '<span class="span3">' . __ ( 'user.' . $info, $info ) . '</span><span>:</span>';
	$retval .= '<span class="span8">' . $userInfo->get ( $info ) . '</span>';
	$retval .= '</div>';
	return $retval;
}
echo renderInfo ( $userInfo, 'email' );
echo renderInfo ( $userInfo, 'birth_date' );
if ($persons) {
	ppd($persons);
}
echo '</section>';
?>
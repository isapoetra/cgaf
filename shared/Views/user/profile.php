<?php
use System\Web\Utils\HTMLUtils;
use System\Exceptions\AccessDeniedException;
$acl = $this->getAppOwner ()->getACL ();
$_detail = isset ( $_detail ) ? $_detail : false;
$edit = false;
if ($userInfo) {
	$edit = $acl->isAdmin () || $userInfo->isCurrentUser () && ! $_detail;
}

$msg = isset ( $message ) ? $message : null;
$ov = Request::get ( '__overlay' );
$tplVars = array (
		"edit" => $edit,
		"acl" => $acl,
		'_detail' => $_detail
);
if ($msg) {
	echo '<div id="message">' . $msg . '</div>';
}
?>
<div class="well">
<?php
$av = array('user_name','user_status','date_created','last_access','last_ip');
foreach($av as $v) {
	$cl = 'label-info';
	switch ($v) {
		case 'last_ip':
		case 'last_access':
			$cl= 'label-warning';
		break;
	}
	$val = $userInfo->$v  ? $userInfo->$v  : '-';
	echo '<div class="row show-grid">';
	echo '<div class="span2">'. __('user.'.$v) .'</div>';			
	echo '<div class="label '.$cl.' span3">'.$val .'</div>';
	echo '</div>';
}
echo '<h2>Registered Person By You</h2>';
echo '<ul>';
foreach($persons as $p) {
	echo '<li>';
	echo '<a href="' . \URLHelper::add(APP_URL,'/person/info/?id='.$p->person_id)  . '">'.$p->fullname.'</a>';
	echo '<div class="actions">';
	if (!$p->isprimary) {
		echo '<a href="#">Set As Primary</a>';
	}
	echo '</div>';
	echo '</li>';
}
echo '</ul>';
?>
</div>


<?php
$user_profile = isset($user_profile) && $user_profile ? $user_profile : \FBUtils::getUserProfile();
if (!$user_profile) {
	$url = $fb->getLoginUrl();
	echo '<a href="'.$url.'">Login With FaceBook</a>';
	return;
}
$row =new \stdClass();
\Utils::bindToObject($row,$user_profile,true);
$row->user_name = $user_profile['username'];
$row->first_name =$user_profile['first_name'];
echo $appOwner->renderView('register',null,array(
	'actionurl'=>BASE_URL.'user/fbregister',
	'row'=>$row
),'user');

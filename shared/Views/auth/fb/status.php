<?php
$controller = $this->getController('user');
$user = isset($user) ? $user : null;
$error = isset($error) ? $error : null;
echo '<div class="fb">';
if ($error) {
	echo '<span class="error">' . $error . 'please <a href="javascript:window.location.reload();">try again</a><span>';
	return;
}
if ($user) {
	echo 'Facebook Logged as <a href="' . $user_profile['link'] . '"/>' . $user_profile['name'] . '</a>';
	if (!$ruser) {
		echo '<a href="' . BASE_URL . '/user/fbregister/?authmode=true">Register to our system</a>';
	} elseif (!$appOwner->isAuthentificated()) {
		$ajax = \Request::isAJAXRequest() ? '__ajax=1' : '';
		echo '<div>Not you ? click here <a href="' . $fb->getLogoutUrl() . '">     <img src="http://static.ak.fbcdn.net/rsrc.php/z2Y31/hash/cxrz4k7j.gif"></a></div>';
		echo '<a href="' . \URLHelper::add(APP_URL, 'auth/facebook', '__confirm=1&state=' . \Request::get('state', false) . '&' . $ajax) . '">login to our system using this credential</a>';
	} elseif ($appOwner->isAuthentificated()) {
		if (\Request::isAJAXRequest()) {
			echo 'Please close this window';
		}
	}
} else {
	$rurl = $fb->getLoginURL();
	$fburl = \URLHelper::add(APP_URL, 'auth/facebook', '__ajax=1');
	$url = $fb
			->getLoginURL(
					array(
							'display' => 'popup',
							'redirect_uri' => $fburl,
							'next' => \URLHelper::addParam($fburl, 'loginsucc=1'),
							'cancel_url' => \URLHelper::addParam($fburl, 'cancel=1'),
							'req_perms' => 'email,user_birthday'));
	echo '<a href="' . $url . '" id="fb-login-button"><img src="http://static.ak.fbcdn.net/rsrc.php/zB6N8/hash/4li2k73z.gif"></a>';
	//echo '<fb:login-button show-faces="true" width="200" max-rows="1"></fb:login-button>';
}
echo '</div>';

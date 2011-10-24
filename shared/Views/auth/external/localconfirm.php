<?php
$ajax = \Request::isAJAXRequest() ? '__ajax=1' : '';
echo 'Facebook Logged as <a href="' . $remoteuser->profilelink . '"/>' . $remoteuser->name . '</a>';
echo '<div>Not you ? click here <a href="' . $provider->getLogoutUrl() . '">     <img src="http://static.ak.fbcdn.net/rsrc.php/z2Y31/hash/cxrz4k7j.gif"></a></div>';
if (!$localuser) {
	echo '<a href="' . BASE_URL . '/auth/external/?mode=rtl">Register to our system</a>';
}
echo '<a href="' . \URLHelper::add(APP_URL, 'auth/external', 'id=' . $providername . '&__confirm=1&state=' . \Request::get('state', false) . '&' . $ajax) . '">login to our system using this credential</a>';

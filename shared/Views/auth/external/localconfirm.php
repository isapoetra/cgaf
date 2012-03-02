<?php
if (\Request::isAJAXRequest()) {
	echo $this->render('headersimple',true,false);
}
echo 'External Logged as <a href="' . $remoteuser->profilelink . '"/>' . $remoteuser->name . '</a>';
echo '<div>Not you ? click here <a href="' . $provider->getLogoutUrl() . '">     <img src="http://static.ak.fbcdn.net/rsrc.php/z2Y31/hash/cxrz4k7j.gif"></a></div>';
if (!$localuser) {
	echo '<a href="' . \URLHelper::add($authurl,null,'mode=rtl')  .'">Register to our system</a>';
}else{
	echo '<a href="' . \URLHelper::add($authurl, null,'__confirm=1') . '">login to our system using this credential</a>';
}

if (\Request::isAJAXRequest()) {
	echo $this->render('footersimple',true,false);
}

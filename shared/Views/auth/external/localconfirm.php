<div class="well"><?php
if (\Request::isAJAXRequest()) {
	echo $this->render('headersimple',true,false);
}
echo 'External Logged as <a href="' . $remoteuser->profilelink . '"/>' . $remoteuser->name . '</a>';
echo '<hr/>';
if (!$localuser) {
	echo '<div>Not you ?  <a href="' . $provider->getLogoutUrl() . '">click here</a></div>';
	echo '<a href="' . \URLHelper::add($authurl,null,'mode=rtl')  .'">Register to our system</a>';
}else{	
	echo '<a href="' . \URLHelper::add($authurl, null,'__confirm=1') . '">Login with this credential</a>';
}

if (\Request::isAJAXRequest()) {
	echo $this->render('footersimple',true,false);
}
?>
</div>
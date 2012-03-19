<?php
use \System\Web\Utils\HTMLUtils;
if (\Request::isAJAXRequest()) {
	echo $this->render('headersimple',true,false);
}
if (isset($message['__common'])) {
	echo '<span class="label label-important">'.\Convert::toString($message['__common']).'</span>';
}
echo HTMLUtils::beginForm('',false,false);
//$appOwner->AddClientAsset(array('bootstrap/css/bootsrap.css'));
echo HTMLUtils::renderTextBox(__('user.logon_name'), 'user_name',$remoteuser->email,null,false);
echo HTMLUtils::renderDateInput(__('user.birth_date'),'birth_date',$remoteuser->birth_date,null,true,@$message['birth_date']);
echo HTMLUtils::endForm(true,true,false);
if (\Request::isAJAXRequest()) {
	echo $this->render('footersimple',true,false);
}
?>
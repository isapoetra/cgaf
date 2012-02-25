<?php
use \System\Web\Utils\HTMLUtils;
echo $this->Render('shared/headersimple.php',true,false);
echo HTMLUtils::beginForm('',false,false);
pp($remoteuser);
//$appOwner->AddClientAsset(array('bootstrap/css/bootsrap.css'));
if (isset($message)) {

	foreach($message as $k=>$v) {
		echo '<ul class="nav nav-list">'.$k;
		echo '<li class="nav nav-header">'.$k.'</li>';
		foreach ($v as $vv) {
			echo '<li class="label-important">'.$vv.'</li>';
		}
		echo '</ul>';
	}
}
echo HTMLUtils::renderTextBox(__('user.user_name'), 'user_name',$remoteuser->email,null,false);
echo HTMLUtils::renderDateInput(__('birthdate'),'birth_date',$remoteuser->birth_date);
echo HTMLUtils::endForm(true,true,false);
?>
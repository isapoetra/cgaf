<?php
use System\Web\Utils\HTMLUtils;
echo HTMLUtils::beginForm('',false,false);
echo HTMLUtils::renderTextBox(__('user.emailconfirm', 'Please Enter Your email'),'email');
echo HTMLUtils::endForm(true,true);
?>
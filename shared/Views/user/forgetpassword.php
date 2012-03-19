<?php
use System\Web\Utils\HTMLUtils;
if ($error) {
	echo HTMLUtils::renderError($error);
}
echo HTMLUtils::beginForm('');
echo HTMLUtils::renderTextBox(__('login'), 'login',@$row->login);
echo HTMLUtils::renderDateInput(__('birth_date'), 'birth_date',@$row->birth_date);
echo HTMLUtils::renderCaptcha();
echo HTMLUtils::endForm(true,true);
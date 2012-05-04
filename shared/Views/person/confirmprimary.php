<?php
use System\Web\Utils\HTMLUtils;
$cp = PersonData::getPrimaryCurrentUser();
if ($cp) {
	ppd($cp);
}
echo HTMLUtils::beginForm('',false);
echo HTMLUtils::renderCheckbox('Confirm', '__confirm');
echo HTMLUtils::endForm(true,true);
?>
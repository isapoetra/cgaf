<?php
use System\Web\UI\Controls\WebControl;

if (!$actions) {
	return null;
}
echo '<div class="btn-group actions-list">';
foreach ($actions as $act) {
	if ($act instanceof WebControl) {
		$act->addClass('btn');
	}
	echo \Convert::toString($act);
}
echo '</div>';
?>
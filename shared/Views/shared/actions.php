<?php
if (!$actions) {
	return null;
}
echo '<ul class="nav nav-pills">';
foreach ($actions as $act) {
	echo '<li>'.\Convert::toString($act).'</li>';
}
echo '</ul>';
?>
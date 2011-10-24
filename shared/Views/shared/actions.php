<?php
if (!$actions) {
	return null;
}
echo '<ul class="actions">';
foreach ($actions as $act) {
	echo '<li class="item">'.\Utils::toString($act).'</li>';
}
echo '</ul>';
?>
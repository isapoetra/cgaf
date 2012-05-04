<?php
use System\Web\Utils\HTMLUtils;
if (!$rows) {
	echo HTMLUtils::renderError(__('user.my.noperson'));
}else{
	echo '<ul class="thumbnails">';
	foreach ($rows as $p) {
		echo '<li  class="span2">';
		echo '<div class="thumbnail">';
		echo '<a class="thumbnail" href="' . \URLHelper::add(APP_URL, '/person/detail/?id=' . $p->person_id) . '">';
		echo '<img src="' . \URLHelper::add(APP_URL, '/person/image/?id=' . $p->person_id . '&size=140x100') . '" alt="Person Image">';
		echo '<h5  class="label' . ($p->isprimary ? ' label-info' : '') . '">' . $p->fullname . '</h5>';
		echo '</a>';
		echo '</div>';
		echo '</li>';
	}
	echo '</ul>';
}
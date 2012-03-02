<?php
$mode = isset($mode) ? $mode : Request::get('mode', 'full');
$rows = isset($rows) ? $rows : array();
$lang = $appOwner->getLocale ()->getLocale ();
if ($mode == 'full') {
	echo '<h3>';
	echo __('locale.list', 'List of installed Locale');
	echo '</h3>';
	}
echo '<ul>';
foreach ($rows as $k => $v) {
	echo '<li><a href="' . BASE_URL . 'locale/select/?id=' . $k . '"><img src="' . BASE_URL . '/assets/images/flags/' . $k . '.png"/><span>' . $v . '</span></a></li>';
}
echo '</ul>';

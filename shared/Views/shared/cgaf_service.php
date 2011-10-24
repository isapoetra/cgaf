<?php
use \System\Web\Utils\HTMLUtils;
$apps = AppManager::getInstalledApp();
if ($apps) {
	echo '<ul class="app_list" style="display:none">';
	$appId = AppManager::getActiveApp();
	echo '<li>' . HTMLUtils::renderLink(BASE_URL . "?__appId=" . CGAF::getConfig("desktopApp", "desktop"), 'Dekstop') . '</li>';
	foreach ($apps as $k => $v) {
		if ((int) $v->app_id !== -1 && (int) $v->app_state === 1) {
			echo '<li' . ($v->app_id === $appId ? ' class="active"' : '') . '>'
					. HTMLUtils::renderLink(BASE_URL . "?__appId=" . $v->app_id, ucwords($v->app_name), null, $v->app_icon) . '</li>';
		}
	}
	echo '</ul>';
}
?>
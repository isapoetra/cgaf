<?php
$defAppIcon = isset($defAppIcon) ? $defAppIcon : Utils::PathToLive(CGAF::getConfig("defaultAppIcon", SITE_PATH
		. '/assets/images/icons/default-app.png'));
$rendericon = isset($rendericon) ? $rendericon : true;

function renderAppItem($row, $rendericon, $defAppIcon, $install = false) {
	if ($install) {
	} else {
		$link = BASE_URL . '?__appId=' . $row->app_id;
	}
	if ($row->app_id===\CGAF::APP_ID) return;
	$activeApp = AppManager::getActiveApp();
	echo '<li' . ($activeApp === $row->app_id ? ' class="active"' : '') . ' >';
	if ($rendericon) {
		$icon = $row->app_icon ? $row->app_icon : $row->app_id . '.png';
		$iconf = SITE_PATH . '/assets/images/icons/applications/' . $icon;
		if (is_file($iconf)) {
			$icon = Utils::PathToLive($iconf);
		} else {
			$icon = $defAppIcon;
		}
		echo '<img src="' . $icon . '" alt="Logo" title="' . $row->app_name . ' Logo"/>';
	}
	echo '<a href="' . $link . '">';
	echo '<span>' . $row->app_name . '&nbsp;v.' . $row->app_version . '</span></a></li>';
}
echo '<div id="cgaf-applist">';
$lst = AppManager::getNotInstalledApp();
if ($lst) {
	echo '<h2 class="not-installed">Not Installed Applications</h2>';
	echo "<nav>";
	echo "<ul>";
	foreach ($lst as $l) {
		echo '<li>';
		if ($rendericon) {
			echo '<img src="' . BASE_URL . '/assets/images/icons/install-app.png"/>';
		}
		echo '<a href="/__cgaf/_installApp/?id=' . $l . '"><span>' . $l . '</span></a>';
		echo '</li>';
	}
	echo "</ul>";
	echo "</nav>";
}
$rows = AppManager::AllowedApp();
if ($rows) {
	echo '<h2 class="installed">Installed Applications</h2>';
	echo '<ul>';
	foreach ($rows as $row) {
		renderAppItem($row, $rendericon, $defAppIcon);
	}
	echo '<ul>';
}
echo "</div>";
?>
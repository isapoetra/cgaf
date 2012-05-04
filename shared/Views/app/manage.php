<?php
use System\Web\Utils\HTMLUtils;

use System\ACL\ACLHelper;
if (isset($msg) && $msg) {
	echo HTMLUtils::renderError($msg);
}
$ctl = $this->getController();
$list = \AppManager::getInstalledApp(false);
$manageMode = CGAF::isAllow('system', 'manage', ACLHelper::ACCESS_MANAGE);
if ($manageMode) {
	echo '<a href="' . \URLHelper::add(BASE_URL, '/app/install/') . '" class="btn btn-primary">Install</a>';
}
echo '<h5>Installed Application</h5>';
echo '<ul class="thumbnails" id="app-list">';
if ($list) {
	$def = \CGAF::getConfig('cgaf.defaultAppId', \CGAF::APP_ID);
	foreach ($list as $app) {
		echo '<li class="span2">';
		echo '<div class="thumbnail">';
		echo  '<a href="' . (( bool )$app->active ? \URLHelper::add(BASE_URL, '/app/select/?appid=' . $app->app_id) : '#')
			. '"  class="thumbnail' . ($app->app_id === $def ? ' default' : '') . '" rel="tooltip" title="Click to view information">';
		echo '<img src="' . \URLHelper::add(APP_URL, '/app/thumb/?id=' . $app->app_id . '&size=140x100') . '"  style="width:140;height:100" alt="app logo">';
		echo '</a>';
		echo '<h5>' . $app->app_name . '</h5>';
		echo '<div class="caption">';
		if ($manageMode) {
			echo '<ul class="nav nav-list">';
			echo  '<li class="nav-header">Systems</li>';
			if ($ctl->isAllow('uninstall')) {
				echo '<li><a  href="' . BASE_URL . 'app/uninstall/?id=' . $app->app_id . '">Uninstall</a></li>';
			}
			if ($ctl->isAllow('dumpdb')) {
				echo '<li><a  href="' . BASE_URL . 'app/dumpdb/?id=' . $app->app_id . '">Dump Database</a></li>';
			}
			switch (( bool )$app->active) {
				case true :
					if ($ctl->isAllow('deactivate')) {
						echo '<li><a  href="' . BASE_URL . 'app/deactivate/?id=' . $app->app_id . '">Deactivate</a></li>';
					}
					break;
				case false :
					if ($ctl->isAllow('activate')) {
						echo '<li><a  href="' . BASE_URL . 'app/activate/?id=' . $app->app_id . '">Activate</a></li>';
					}
			}
			echo '<li class="nav-header">Manage</li>';
			echo '<li><a href="' . BASE_URL . 'acl/manage/?appid=' . $app->app_id . '"><i class="icon-user"></i>ACL</a></li>';
			echo'<li><a href="' . BASE_URL . 'contents/manage/?appid=' . $app->app_id
				. '"><i class="icon-gift"></i>Contents</a></li>';
			echo'<li><a href="' . BASE_URL . 'menus/manage/?appid=' . $app->app_id
				. '"><i class="icon-list-alt"></i>Menus</a></li>';
			$access = \CGAF::getACL()->getUserPriv(ACLHelper::PUBLIC_USER_ID, $app->app_id, ACLHelper::APP_GROUP, \CGAF::APP_ID);

			if (!ACLHelper::isAllowAccess($access, \System\ACL\ACLHelper::ACCESS_VIEW)) {
				echo'<li><a href="' . BASE_URL . 'app/publish/?appid=' . $app->app_id
					. '"><i class="icon-list-alt"></i>Publish</a></li>';
			} else {
				echo'<li><a href="' . BASE_URL . 'app/unpublish/?appid=' . $app->app_id
					. '"><i class="icon-list-alt"></i>Unpublish</a></li>';
			}
			echo '<li class="nav-header">Maintenance</li>';
			echo'<li><a href="' . BASE_URL . 'app/recheck/?appid=' . $app->app_id
				. '"><i class="icon-list-alt"></i>Recheck</a></li>';
			echo'<li><a href="' . BASE_URL . 'app/update/?appid=' . $app->app_id
				. '"><i class="icon-list-alt"></i>Update</a></li>';
			echo '</ul>';
		}
		echo '</div>';
		echo '</div>';
		echo '</li>';
	}
} else {
	echo '<li class="nav-header">aaaaaaaaaaaaaw</li>';
}
echo '</ul>';
if ($manageMode) {
	echo '<h5>Not Installed Application</h5>';
	$list = \AppManager::getNotInstalledApp();
	echo '<ul class="thumbnails">';
	foreach ($list as $app) {
		echo '<li>';
		echo $app;
		echo '<div class="actions">';
		echo '<a href="' . BASE_URL . 'app/install/?id=' . $app . '">Install</a>';
		echo '<div>';
		echo '</li>';
	}
	echo '</ul>';
}
?>
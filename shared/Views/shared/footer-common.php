<?php
use System\Web\UI\Items\MenuItem;

use System\Web\Utils\HTMLUtils;
echo '<div>';
$items = \CGAF::isInstalled() ? $this->getAppOwner()->getMenuItems('footer') :array();
if (\CGAF::isInstalled()) {
	if ($appOwner->getAppId() !==\CGAF::APP_ID) {
		$nitems =array();
		if ($appOwner->getConfig('cgaf.showdesktop',true)) {
			$nitems[] =new MenuItem(array(
					'caption'=>__('app.desktop'),
					'menu_action'=>\URLHelper::add(BASE_URL,'?__appId='.\CGAF::APP_ID)
			));
		}
		$items=array_merge($nitems,$items);
	}
}
echo HTMLUtils::renderMenu($items,null,'nav-pills');
echo '</div>';

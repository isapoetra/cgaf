<?php
use System\Web\UI\Controls\Thumbnail;
use System\Web\UI\Controls\ThumbnailItem;
use System\Web\Utils\HTMLUtils;
use System\Web\UI\Controls\ImageScroll;
use System\ACL\ACLHelper;
if (count ( $rows )) {
	$thumb = new Thumbnail ();
	$rows = \AppManager::AllowedApp ();
	$defAppIcon = $this->getAppOwner ()->getLiveAsset ( \CGAF::getConfig ( "defaultAppIcon", SITE_PATH . '/assets/images/icons/default-app.png' ) );
	foreach ( $rows as $r ) {
		if ($r->app_id === \CGAF::APP_ID)
			continue;
		$link = BASE_URL . '?__appId=' . $r->app_id;
		$icon = BASE_URL . 'asset/get/?appId=' . $r->app_id . '&q=' . ($r->app_icon ? $r->app_icon : 'app' . '.png');
		$ti = new ThumbnailItem ( $r->app_name, $icon, $link );
		$ti->SetDescription ( $r->app_descr ? $r->app_descr : $r->app_name . ' v. ' . $r->app_version );
		$path = AppManager::getAppPath ( $r->app_id );
		if ($appOwner->getACL ()->isInRole ( ACLHelper::DEV_GROUP )) {
			$ti->addAction ( HTMLUtils::renderLink ( BASE_URL . '/applications/remove/?id=' . $r->app_id, 'Uninstall', array (
					'title' => 'Uninstall'
			), 'app/uninstall.png' ) );
		}
		$ti->addAction ( HTMLUtils::renderLink ( \URLHelper::add ( BASE_URL, 'about/app/?id=' . $r->app_id ), 'Info', array (
				'title' => 'about'
		), 'app/about.png' ) );
		$screenshoots = \CGAF::getAppScreenShoots ( $r->app_id );
		if ($screenshoots) {
			$ssc = new ImageScroll ( $screenshoots );
			$ti->addChild ( $ssc );
		}
		$thumb->addChild ( $ti );
	}
	echo $thumb->render ( true );
} else {
	echo '<div class="label label-warning">'.__('app.empty').'</div>';
}
//$this->getView("shared","appthumb",array("rendericon"=>true)));

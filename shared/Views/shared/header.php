<?php
defined('CGAF') || die('restricted access');
if ($this->getAppOwner()->parent) {
	return;
}
use System\Session\Session;
$controller = isset($controller) ? $controller : $this->getController();
$appOwner = $this->getAppOwner();
$bodyattr = $this->getVar('bodyattr');
$jsEngine = $appOwner->getJSEngine();
$appinfo = $appOwner->getAppInfo();
$title = isset($title) ? $title : $appOwner->getConfig("site.title", CGAF::getConfig("cgaf.description") . ' v.' . CGAF_VERSION);
$hcontent = $appOwner->renderContent('header');
$umenu = $this->render("shared/usermenu", true, false);
$crumb = $this->render('shared/crumb', true, false);
$manifest = '';
if ($appOwner->getConfig('allowoffline')) {
	$manifest = $appOwner->getAppManifest();
	$manifest = 'manifest="' . $manifest . '"';
}
//
echo <<< EOT
<!DOCTYPE html>
<html $manifest>
<head profile="http://a9.com/-/spec/opensearch/1.1/">
<title>$title</title>
EOT;
echo $appOwner->renderMetaHead();
if ((\Request::isMobile() && !\Request::isAJAXRequest()) || !\Request::isMobile()) {
	echo $appOwner->renderClientAsset("css");
}
$s = $appOwner->getStyleSheet();
if ($s) {
	echo '<style type="text/css" style="display: none !important; ">';
	foreach ($s as $ss) {
		echo Utils::toString($ss);
	}
	echo '</style>';
}
//$appOwner->addClientAsset('http://4webdevelopers.appspot.com/application-cache/debug.js');
echo '</head>';
echo '<body ' . ($bodyattr ? $bodyattr : '') . '>';
//if ((\Request::isMobile() && !\Request::isAJAXRequest()) || !\Request::isMobile()) {
$mbar = $appOwner->renderMenu('menu-bar');
//}
if (\Request::isMobile()) {
	$appUrl = APP_URL;
	echo <<< EOT
<div data-role="page" id="main" class="type-interior">
	<div data-role="header">
	<div data-role="header" data-theme="f">
		<h1>$title</h1>
		<a href="$appUrl" data-icon="home" data-iconpos="notext" data-direction="reverse" class="ui-btn-right jqm-home">Home</a>
	</div><!-- /header -->
	$mbar
	</div>
	<div data-role="content">
EOT;
} else {
	echo '<div id="sysmessage"></div>';
	echo '<div id="wrapper" class="wrapper wrapper-' . $appinfo->app_name . '">';
	if (!Session::get('clientValidated')) {
		echo $this->render('shared/noscript', true, false);
	}
	echo '<div id="wrapper-top"' . ($crumb ? 'class="hascrumb"' : '') . '>';
	echo '<div id="navigation-top">';
	echo $mbar;
	echo $umenu;
	echo '</div>';
	echo $crumb;
	echo $hcontent;
	echo '</div>';
	echo '<div id="wrapper-content"' . ($crumb ? ' class="hascrumb"' : '') . '>';
}
?>

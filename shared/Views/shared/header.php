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
echo <<< EOT
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" lang="en" xml:lang="en">
<head profile="http://a9.com/-/spec/opensearch/1.1/">
<title>$title</title>
EOT;
echo $appOwner->renderMetaHead();
echo $appOwner->renderClientAsset("css");
$s = $appOwner->getStyleSheet();
if ($s) {
	echo '<style type="text/css" style="display: none !important; ">';
	foreach ($s as $ss) {
		echo Utils::toString($ss);
	}
	echo '</style>';
}
echo '</head>';
echo '<body ' . ($bodyattr ? $bodyattr : '') . '>';
echo '<div id="sysmessage"></div>';
echo '<div id="wrapper" class="wrapper wrapper-' . $appinfo->app_name . '">';
if (!Session::get('clientValidated')) {
	echo $this->render('shared/noscript', true, false);
}
echo '<div id="wrapper-top"'.($crumb ? 'class="hascrumb"' : '').'>';
echo '<div id="navigation-top">';
echo $appOwner->renderMenu('menu-bar');
echo $umenu;
echo '</div>';
echo $crumb;
echo $hcontent;
echo '</div>';
echo '<div id="wrapper-content"' . ($crumb ? ' class="hascrumb"' : '') . '>';
?>
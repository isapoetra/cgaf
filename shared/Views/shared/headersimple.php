<?php
defined ( 'CGAF' ) || die ( 'restricted access' );
$appOwner = isset ( $appOwner ) ? $appOwner : $this->getAppOwner ();
if ($appOwner->parent) {
	return;
}
$appinfo = $appOwner->getAppInfo ();
$title = isset ( $title ) ? $title : $appOwner->getConfig ( "site.title", CGAF::getConfig ( "cgaf.description" ) . ' v.' . CGAF_VERSION );
$lang = $appOwner->getLocale ()->getLocale ();
echo <<< EOT
<!DOCTYPE html>
<!--[if lt IE 7]> <html class="no-js lt-ie9 lt-ie8 lt-ie7" lang="$lang"> <![endif]-->
<!--[if IE 7]>    <html class="no-js lt-ie9 lt-ie8" lang="$lang"> <![endif]-->
<!--[if IE 8]>    <html class="no-js lt-ie9" lang="$lang"> <![endif]-->
<!--[if gt IE 8]><!--> <html class="no-js" lang="$lang"> <!--<![endif]-->
<head profile="http://a9.com/-/spec/opensearch/1.1/">
<title>$title</title>
EOT;
echo $appOwner->renderMetaHead ();
echo $appOwner->renderClientAsset ( "css" );
echo <<< EOT
<style  type="text/css" style="display: none !important; ">
body {
	padding:0;
	margin:0;
	overflow:hidden;
}
#wrapper {
	overflow:autol
}
</style>
EOT;
echo '</head>';
echo '<body>';
// if ((\Request::isMobile() && !\Request::isAJAXRequest()) ||
// !\Request::isMobile()) {
$mbar = $appOwner->renderMenu ( 'menu-bar', false, null, 'navbar', false );
// echo '<div id="wrapper-top"' . ($crumb ? 'class="hascrumb"' : '') . '>';
echo '<div id="navigation-top" class="navbar navbar-fixed-top">';
echo '	<div class="navbar-inner">';
echo '		<div class="container">';
echo '			<a class="brand" href="' . APP_URL . '">' . $appOwner->GetAppName () . '</a>';
echo '		<div class="nav-collapse">';
echo '		</div>';
echo '	</div>';
echo '</div>';
echo '</div>';
echo '<div id="wrapper" class="container-fluid wrapper wrapper-' . $appinfo->app_name . '">';

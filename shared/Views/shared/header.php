<?php
defined ( 'CGAF' ) || die ( 'restricted access' );
$appOwner = isset ( $appOwner ) ? $appOwner : $this->getAppOwner ();
if ($appOwner->parent) {
	return;
}
use System\Session\Session;
use System\Web\UI\Controls\BreadCrumb;
$controller = isset ( $controller ) ? $controller : $this->getController ();
$bodyattr = $this->getVar ( 'bodyattr' );
$jsEngine = $appOwner->getJSEngine ();
$appinfo = $appOwner->getAppInfo ();
$title = isset ( $title ) ? $title : $appOwner->getConfig ( "site.title", CGAF::getConfig ( "cgaf.description" ) . ' v.' . CGAF_VERSION );
$hcontent = \CGAF::isInstalled() ? $appOwner->renderContent ( 'header' ) : null;
$umenu = $this->render ( "shared/usermenu", true, false );

$crumb = '';
// $this->render ( 'shared/crumb', true, false );
if ($appOwner->getConfig ( 'site.showcrumb', true )) {
	if (! isset ( $crumbs )) {
		$crumbs = $appOwner->getCrumbs ();
	}
	$bc = new BreadCrumb ();
	$bc->addItems ( $crumbs );
	$crumb = $bc->render ( true );
}
$manifest = $appOwner->getAppManifest ();
$lang = $appOwner->getLocale ()->getLocale ();
$manifest = $manifest ? 'manifest="' . $manifest . '"' : '';
echo <<< EOT
<!DOCTYPE html>
<!--[if lt IE 7]> <html class="no-js lt-ie9 lt-ie8 lt-ie7" lang="$lang"> <![endif]-->
<!--[if IE 7]>    <html class="no-js lt-ie9 lt-ie8" lang="$lang"> <![endif]-->
<!--[if IE 8]>    <html class="no-js lt-ie9" lang="$lang" $manifest> <![endif]-->
<!--[if gt IE 8]><!--> <html class="no-js" lang="$lang" $manifest> <!--<![endif]-->
<head profile="http://a9.com/-/spec/opensearch/1.1/">
<title>$title</title>
EOT;
echo $appOwner->renderMetaHead ();
if ((\Request::isMobile () && ! \Request::isAJAXRequest ()) || ! \Request::isMobile ()) {
	echo $appOwner->renderClientAsset ( "css" );
}
$s = $appOwner->getStyleSheet ();
echo '<style type="text/css" style="display: none !important; ">';
if ($s) {
	foreach ( $s as $ss ) {
		echo Convert::toString ( $ss );
	}
}
echo '</style>';
echo '</head>';
echo '<body ' . ($bodyattr ? $bodyattr : '') . '>';
// if ((\Request::isMobile() && !\Request::isAJAXRequest()) ||
// !\Request::isMobile()) {
$mbar = \CGAF::isInstalled() ? $appOwner->renderMenu ( 'menu-bar', false, null, 'navbar', false ) : null;
// echo '<div id="wrapper-top"' . ($crumb ? 'class="hascrumb"' : '') . '>';
echo '<div id="navigation-top" class="navbar navbar-fixed-top">';
echo '	<div class="navbar-inner">';
echo '		<div class="container">';
echo <<< EOT
<a class="btn btn-navbar" data-toggle="collapse" data-target=".nav-collapse">
<span class="icon-bar"></span>
<span class="icon-bar"></span>
<span class="icon-bar"></span>
</a>
EOT;
echo '<a class="brand" href="' . APP_URL . '">' . $appOwner->getConfig('app.shortname',$appOwner->GetAppName ()) . '</a>';
echo $umenu;
echo '		<div class="nav-collapse">';
echo $mbar;
echo '		</div>';
echo $crumb;
echo '	</div>';
echo '</div>';
echo '</div>';
// }
if (\Request::isMobile ()) {
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
	echo '<div id="wrapper" class="container-fluid wrapper wrapper-' . $appinfo->app_name .($crumb ? ' hascrumb' :'') .'">';
	echo '<div class="wrapper-inner">';
	echo '<div id="sysmessage" class="alert  alert-error"><a class="close" data-dismiss="alert">&times;</a><h4 class="alert-heading">Warning!</h4></div>';
	if (! Session::get ( 'clientValidated' )) {
		echo $this->render ( 'shared/noscript', true, false );
	}
	echo $hcontent;
}
?>

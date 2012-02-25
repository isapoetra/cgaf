<?php
use System\Web\JS\CGAFJS;
use System\Web\JS\JSUtils;
$appOwner = isset ( $appOwner ) ? $appOwner : $this->getAppOwner ();
if ($this->getAppOwner ()->parent)
	return;
$share = null;
$footer = $appOwner->renderContent ( 'footer' );
echo $footer ? '<div>' . $footer . '</div>' : '';
echo '</div><!-- EOF page/wrapper -->';
echo '<br/>';
if (! \Request::isMobile ()) {
	echo '<footer id="wrapper-footer" class="footer">';
	echo '<p class="pull-right cgaf-powered"><a href="' . BASE_URL . 'about/cgaf">Powered by CGAF ' . CGAF_VERSION . (CGAF_DEBUG ? '&nbsp;<span class="error">DEBUG MODE</span>' : '') . '</a></p>';
	echo $this->render ( 'shared/footer-common', true );
	//echo '<span class="r-address"> Your IP' . $_SERVER ['REMOTE_ADDR'] . '</span>';
	echo '</footer>';
}
//echo '</div><!--- EOF Page-->';

if (CGAF::isDebugMode ()) {
	echo $this->render ( "app-log", true );
}
echo $appOwner->renderClientAsset ( "js" );
if (Request::get ( '__msg' )) {
	echo '$("#sysmessage").html(\'' . Request::get ( '__msg' ) . '\').show(\'slow\'); ';
}
echo '</script>';
echo JSUtils::renderJSTag ( $appOwner->getClientDirectScript (), false );
echo CGAFJS::Render ( $appOwner->getClientScript () );
echo '</body>';
echo '</html>';
?>

<?php
use \System\Web\JS\CGAFJS;
use System\Web\JS\JSUtils;
if ($this->getAppOwner()->parent)
	return;
$share = null;
$footer = $appOwner->renderContent('footer');
echo '</div><!-- EOF page/wrapper -->';
if (Request::isMobile()) {
	echo '<div data-role="footer" class="footer-docs" data-theme="c">';
	echo '<span class="cgaf-powered"><a href="' . BASE_URL . 'about/cgaf">Powered by CGAF ' . CGAF_VERSION . (CGAF_DEBUG ? '&nbsp;<span class="error">DEBUG MODE</span>' : '') . '</a></span>';
	echo '</div>';
}
if ($footer) {
	echo '<div class="footer">' . $footer . '</div>';
}
echo $appOwner->renderClientAsset("js");
echo <<< EOT
	<script type="text/javascript">
		$("#sysmessage").bind('click', function() {
			$(this).hide();
		});
EOT;
if (Request::get('__msg')) {
	echo '$("#sysmessage").html(\'' . Request::get('__msg') . '\').show(\'slow\'); ';
}
echo '</script>';
if (!\Request::isMobile()) {
	echo '<div id="wrapper-footer" class="ui-helper-reset ui-helper-clearfix footer" style="clear: both;">';
	echo $this->render('shared/footer-common', true);
	echo '<span class="cgaf-powered"><a href="' . BASE_URL . 'about/cgaf">Powered by CGAF ' . CGAF_VERSION . (CGAF_DEBUG ? '&nbsp;<span class="error">DEBUG MODE</span>' : '') . '</a></span>';
	echo '<span class="r-address"> Your IP' . $_SERVER['REMOTE_ADDR'] . '</span>';
	echo '</div>';
}
echo '</div><!--- EOF Page-->';
if (CGAF::isDebugMode()) {
	echo $this->render("app-log", true);
}
echo JSUtils::renderJSTag($appOwner->getClientDirectScript(), false);
echo CGAFJS::Render($appOwner->getClientScript());
echo '</body>';
echo '</html>';
?>

<?php
use \System\Web\JS\CGAFJS;
use System\Web\JS\JSUtils;
if ($this->getAppOwner()->parent)
	return;
$share = null;
$footer = $appOwner->renderContent('footer');
/*if ($appOwner->getConfig('social.share', true)) {
    CGAFJS::loadPlugin('social-share', true);
    $appOwner->addClientScript('$.socialshare();');
}*/
?>
</div>
<?php
if ($footer) {
	echo '<div class="footer">' . $footer . '</div>';
}
echo $appOwner->renderClientAsset("js");
	  ?>
<script type="text/javascript">
	$("#sysmessage").bind('click', function() {
		$(this).hide();
	});
<?php
if (Request::get('__msg')) {
	echo '$("#sysmessage").html(\'' . Request::get('__msg') . '\').show(\'slow\'); ';
}
?>

</script>
<div id="wrapper-footer"
	class="ui-helper-reset ui-helper-clearfix footer" style="clear: both;">


	<?php echo $this->render('shared/footer-common', true);
echo '<span class="cgaf-powered"><a href="' . BASE_URL . 'about/cgaf">Powered by CGAF ' . CGAF_VERSION . (CGAF_DEBUG ? '&nbsp;<span class="error">DEBUG MODE</span>' : '') . '</a></span>';
																		   ?>
</div>
</div>
<?php
if (CGAF::isDebugMode()) {
	echo $this->render("app-log", true);
}
echo JSUtils::renderJSTag($appOwner->getClientDirectScript(), false);
echo CGAFJS::Render($appOwner->getClientScript());
						   ?>
</body>
</html>

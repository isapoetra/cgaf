<?php
$items = $appOwner->getMenuItems('app-menu', '-', null, true, true, false);
if (!$items && !CGAF_DEBUG) {
	return;
}
$s = <<< s
$('#app-brand').click(function(e){
	 e.preventDefault();
	 $('#app-menu-container').toggle();
})
s;

$appOwner->addClientScript($s);
//TODO Move to style
?>
<div class="app-menu-container" id="app-menu-container">
	<div class="triangle"><i class="icon  icon-chevron-up"></i></div>
	<a href="#" onclick="$('#app-menu-container').hide();" class="close"><i class="icon icon-remove"></i></a>

	<div class="content">

	</div>
</div>

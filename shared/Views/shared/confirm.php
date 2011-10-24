<?php
use System\Web\Utils\HTMLUtils;
?>
<div class="confirm">
	<div class="ui-widget-header"><?php echo $title ?></div>
	<div class="ui-widget-content"><?php echo $descr ?></div>
<div class="actions">
	<?php echo HTMLUtils::beginForm('');
echo HTMLUtils::renderButton('submit', __('No'), __('message.confirm.no.descr'), array(
		'name' => '__confirm',
		'value' => 'no'));
echo HTMLUtils::renderButton('submit', __('Yes'), __('message.confirm.yes.descr'), array(
		'name' => '__confirm',
		'value' => 'yes'));

echo HTMLUtils::endForm(false, true, false);
								  ?>
</div>
</div>
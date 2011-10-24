<?php
$rows = isset($rows) ? $rows : null;
$s = isset($s) ? $s : null;
if (!$rows) {
	echo sprintf(__('person.search.notfound'), $s);
	return;
}
$id = Utils::generateId('se');
echo '<ul id="' . $id . '">';
$mode = isset($mode) ? $mode : 'simple';
foreach ($rows as $row) {
	echo '<li class="item" value="' . $row->user_id . '" style="overflow:hidden">';
	echo $this->render('single', true, false, array('row' => $row, 'mode' => $mode, 'renderaction' => true));
	echo '</li>';
}
echo '</ul>';
$script = <<< EOT
$(function() {
	$('#$id').find('li').bind('mouseover mouseout',function(e) {
		$(this).parent().find('li .action').hide();
		$(this).find('.action').show();
	});
});
EOT;
$appOwner->addClientScript($script);
<?php
use System\Web\Utils\HTMLUtils;
if (!$row || !$items) {
	return;
}
$msg = isset($msg) ? $msg : '';
echo '<div class="vote vote-' . $row->vote_id . '">';
echo '<h2>' . $row->vote_title . '</h2>';

echo  HTMLUtils::renderError($msg);

echo HTMLUtils::beginForm(BASE_URL . 'vote/vote/');
echo HTMLUtils::renderHiddenField('id', $row->vote_id);
echo '<ul>';
$first = true;
foreach ($items as $item) {
	echo '<li><input type="radio" value="' . $item->vote_item_id . '" name="vote"' . ($first ? ' checked="checked"' : '') . '>' . __($item->vote_title) . '</li>';
	$fist = false;
}
echo '</ul>';
echo '<span>' . __('vote.note') . '</span>';
echo '<textarea name="note"></textarea>';
echo '<input type="submit" value="submit"/>';
echo HTMLUtils::endForm(false, true, false);

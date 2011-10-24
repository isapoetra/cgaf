<?php
use System\Web\Utils\HTMLUtils;
$appOwner->addClientAsset('comment.css');
echo '<div class="comment">';
echo '<div class="comment-head">';
echo '<span class="comment-total">' . ___('comment.total', $total) . '</span>';
if ($add) {
	echo '<a href="javascript:void(0)" onclick="$(\'.comment-first\').hide();$(\'#comment-add-form\').show();$(this).hide();" class="comment-add">' . __('comment.add') . '</a>';
}
echo '</div>';
if (!$commentList) {
	echo '<span class="comment-first"></span>';
}
if ($add) {
	echo HTMLUtils::beginForm(\URLHelper::add(APP_URL, 'comment/store'), false, false, null, array(
			'id' => 'comment-add-form',
			'class' => 'ui-widget-content'));
	//echo HTMLUtils::renderTextBox('comment.comment_title', 'comment_title');
	echo HTMLUtils::renderHiddenField('comment_type', $type);
	echo HTMLUtils::renderHiddenField('appid', $appid);
	echo HTMLUtils::renderHiddenField('comment_item', @$item);
	echo '<div class="comment-container">';
	echo '<textarea required="1" autocomplete="off" placeholder="' . __('comment.addcomment') . '" name="comment_descr">';
	echo '</textarea>';
	echo '</div>';
	echo '<div class="post-external"></div>';
	//echo HTMLUtils::renderTextArea(__('comment.comment_descr'), 'comment_descr', null);
	echo '<input type="submit" value="' . __('comment.comment') . '"/>';
	echo HTMLUtils::endForm(false, true, true);
}
echo $commentList;
echo '</div>';

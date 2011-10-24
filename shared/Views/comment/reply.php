<?php
use System\Web\Utils\HTMLUtils;
echo HTMLUtils::beginForm(\URLHelper::add(APP_URL, 'comment/reply'), false, false, null, array(
		'id' => 'comment-reply-form',
		'class' => 'ui-widget-content'));
//echo HTMLUtils::renderTextBox('comment.comment_title', 'comment_title');
echo HTMLUtils::renderHiddenField('comment_id', @$comment_id);
echo '<div class="comment-container">';
echo '<textarea required="1" autocomplete="off" placeholder="' . __('comment.addcomment') . '" name="comment_descr">';
echo '</textarea>';
echo '</div>';
echo '<div class="post-external"></div>';
//echo HTMLUtils::renderTextArea(__('comment.comment_descr'), 'comment_descr', null);
echo HTMLUtils::renderCaptcha();
echo '<input type="submit" value="' . __('comment.comment') . '"/>';
echo HTMLUtils::endForm(false, true, !\Request::isAJAXRequest());

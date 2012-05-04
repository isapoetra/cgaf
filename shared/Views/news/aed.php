<?php
use System\Web\Utils\HTMLUtils;
echo HTMLUtils::beginForm('../store');
echo HTMLUtils::renderHiddenField('news_id', @$row->news_id);
if (!$row->app_id) {
	echo HTMLUtils::renderTextBox(__('news.app_id'),'app_id', @$row->app_id,null);
}else{
	echo HTMLUtils::renderHiddenField('app_id', @$row->app_id);
}
if (!$row->controller) {
	echo HTMLUtils::renderTextBox(__('news.controller'),'controller', @$row->controller,null);
}else{
	echo HTMLUtils::renderHiddenField('controller', @$row->controller);
}
echo HTMLUtils::renderTextBox(__('news.item'),'item', @$row->item,null,$row->item ==null);
echo HTMLUtils::renderTextBox(__('news.title'), 'title',@$row->title);
echo HTMLUtils::renderTextBox(__('news.type'), 'type',@$row->type);
echo HTMLUtils::renderMarkitupEditor(__('news.descr'), 'descr', @$row->descr);
echo HTMLUtils::endForm(true,true,true);
?>
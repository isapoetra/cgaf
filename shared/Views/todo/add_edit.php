<?php
use System\Web\Utils\HTMLUtils;
use System\MVC\MVCHelper;
echo HTMLUtils::renderHiddenField('todo_id', $row->todo_id);
echo HTMLUtils::renderHiddenField('app_id', $appOwner->getAppId());
echo HTMLUtils::renderTextBox(__('todo.todo'), 'todo_title', $row->todo_title);
echo HTMLUtils::renderSelect(__('todo.state'), 'todo_state', MVCHelper::lookup('todo_state'), $row->todo_state);

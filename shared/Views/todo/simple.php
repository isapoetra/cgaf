<?php
use System\MVC\MVCHelper;
if (!$rows) {
	echo __('todo.empty', 'thereis nothing todo');
	return;
}
$lookup = MVCHelper::lookup('todo-state', '__cgaf');
echo '<ul class="todo">';
foreach ($rows as $row) {
	$state = isset($lookup[$row->todo_state]) ? $lookup[$row->todo_state]->value : 'undecided';
	echo '<li>' . $row->todo_title . '&nbsp;Priority : <span class="priority priority-' . strtolower($state) . '">' . $state . '</span></li>';
}
echo '</ul>';
?>
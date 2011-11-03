<?php
if (!$rows) {
	echo __('todo.empty', 'thereis nothing todo');
	return;
}
echo '<ul class="todo">';
foreach ($rows as $row) {
	echo '<li>' . $row->todo_title . '</li>';
}
echo '</ul>';

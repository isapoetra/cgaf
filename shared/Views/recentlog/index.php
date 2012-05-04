<?php
if (!isset($rows) || ! $rows) {
	return;
}
echo '<ul class="recent-log">';
foreach ($rows as $row) {
	echo '<li>';
	echo '<span class="log-date">'.\DateUtils::formatDate($row->date_created,false).'</span>';
	echo '<span class="title">'.$row->title.'</span>';
	echo '</li>';
}
echo '</ul>';

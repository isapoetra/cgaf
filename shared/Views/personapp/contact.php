<?php
echo '<ul class="p-contacts">';
$cgroup = null;
$first = true;
foreach ($rows as $row) {
	if ($row->detail_type !== $cgroup) {
		if (!$first) {
			echo '</ul></li>';
		}
		$first = false;
		echo '<li class="group-' . $row->detail_type . ' groups">';
		echo '<h2>' . $row->type_name . '</h2>';
		echo '<div class="action">';
		echo '</div>';
		//echo '<h3>' . __('contacts.group.' . $row->type_name . '.descr',$row->type_name) . '</h3>';
		echo '<ul class="list-items">';
	}
	echo '<li class="item">';
	if ($row->title) {
		echo '<h4>' . $row->title . '</h4>';
	}
	echo '<div class="descr">';
	echo '<span class="descr-type-' . $row->detail_type . ' descr-' . $row->id . '">' . ($row->descr ? $row->descr : 'empty') . '</span>';
	if ($row->callback) {
		echo '<span class="callback">';
		echo $row->callback;
		echo '</span>';
	}
	echo '</div>';
	echo $this->getController()->renderActions($row, null, 'personcontact');
	echo '</li>';
	if ($row->detail_type !== $cgroup) {
		$cgroup = $row->detail_type;
	}
}
if ($cgroup !== null) {
	echo '</ul>';
	echo '</li>';
}
echo '</ul>';

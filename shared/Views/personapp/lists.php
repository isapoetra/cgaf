<?php
echo '<div class="ui-widget-header">' . __('personapp.avaliablepersonapp', 'Avaliable Person') . '</div>';
echo '<ul>';
foreach ($rows as $row) {
	echo '<li><a href="" rel="__result" tag="'.$row->person_id.'">'.$row->first_name.' '.$row->last_name.'</a></li>';

}
echo '</ul>';
if ($appOwner->isAllow('person', 'manage')) {
	echo '<a href="' . \URLHelper::add(APP_URL, 'person/aed') . '" rel="__overlay" id="add-person">Add Person</a>';
}

if ($appOwner->isAllow('personapp', 'manage')) {
	echo '<a href="' . \URLHelper::add(APP_URL, 'personapp/aed') . '" rel="__overlay" id="add-person">Assign Person to App</a>';
}

<?php
$mode =\Request::get('mode');
echo '<section>';
echo '<h3>' . $row->FullName . '</h3>';
if ($row->isMe()) {
	echo '<a href="'.\URLHelper::add(APP_URL,'/person/detail/?mode=edit','id='.$row->person_id).'">'.__('edit').'</a>';
}
function renderInfo(\PersonData $userInfo, $info,$mode) {
	$retval = '<div class="row show-grid">';
	$retval .= '<span class="span3">' . __ ( 'user.' . $info, $info ) . '</span>';
	$retval .= '<span class="span8">' . $userInfo->{$info} . '</span>';
	$retval .= '</div>';
	return $retval;
}
echo renderInfo ( $row, 'birth_date',$mode );
echo '</section>';
echo $appOwner->RenderContent ( 'info-bottom', 'person', false, true, array (
		'row' => $row
), true, \CGAF::APP_ID );
?>


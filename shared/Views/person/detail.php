
<?php
function renderInfo(\PersonData $userInfo, $info,$mode) {
	$retval = '<div class="row show-grid">';
	$retval .= '<span class="span3">' . __ ( 'user.' . $info, $info ) . '</span>';
	$retval .= '<span class="span8">' . $userInfo->{$info} . '</span>';
	$retval .= '</div>';
	return $retval;
}
$mode =\Request::get('mode');
echo '<div class="top">';
if ($row->isMe()) {
	echo '<h3><a href="'.\URLHelper::add(APP_URL,'/person/aed/?id='.$row->person_id).'">'.$row->FullName.'</a></h3>';
	if (!$row->isprimary) {
		echo '<a class="btn btn-warning" href="'.\URLHelper::add(APP_URL,'/person/p/?id='.$row->person_id).'">set as primary</a>';
	}elseif ($row->isMe()) {
		echo '<span class="label label-info">Primary</span>';
	}
}else{
	$route = $appOwner->getRoute();
	$ori = $route['_c'] === 'person';
	echo '<h3>' .($ori ? '' : '<a href="'.\URLHelper::add(APP_URL,'/person/detail/?id='.$row->person_id).'">'). $row->FullName .($ori ? '' :'</a>'). '</h3>';
}
echo '<div class="row-fluid show-grid">';
echo '<div class="span2">';
echo '<img src="'.$row->getImage(null,'167x125',true).'"/>';
echo '</div>';
echo '<div>';
echo renderInfo ( $row, 'birth_date',$mode );
echo '</div>';

echo '</div>';
echo '</div>';
echo '</div>';
echo $appOwner->RenderContent ( 'personal-info', 'person', false, true, array (
		'row' => $row
), true, \CGAF::APP_ID );


?>


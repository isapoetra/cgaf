
<?php
echo '<section>';
echo '<h3>' . $row->FullName . '</h3>';
function renderInfo(\PersonData $userInfo, $info) {
	$retval = '<div class="row show-grid">';
	$retval .= '<span class="span3">' . __ ( 'user.' . $info, $info ) . '</span>';
	$retval .= '<span class="span8">' . $userInfo->{$info} . '</span>';
	$retval .= '</div>';
	return $retval;
}
echo renderInfo ( $row, 'birth_date' );
echo '</section>';
echo $appOwner->RenderContent ( 'info-bottom', 'person', false, true, array (
		'row' => $row
), true, \CGAF::APP_ID );
?>


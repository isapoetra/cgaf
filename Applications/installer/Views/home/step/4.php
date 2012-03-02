<?php
defined ( 'CGAF' ) or die ();
use System\Session\Session;
use System\Web\Utils\HTMLUtils;
?>
<div class="well">
	<ul class="nav nav-list">
<?php
$sval = Session::get ( 'install.postedvalues' );
foreach ( $steps as $k => $step ) {
	if ($k < count ( $steps ) - 2) {
		echo '<li class="nav-header">' . $step ['title'] . '</li>';
		echo '<li>';
		echo '<table class="table rounded">';
		foreach ( $sval ['step_' . $k] as $sk => $sv ) {
			echo '<tr>';
			echo '<th>' . __ ( 'install.' . $sk ) . '</th>';
			if (strpos ( $sk, '_password' ) !== false) {
				$sv = '<i>Hidden<i>';
			}
			if (is_bool ( $sv )) {
				$sv = \Utils::bool2yesno ( $sv );
			}
			echo '<td>' . $sv . '</td>';
			echo '<tr>';
		}
		echo '</table>';
		echo '</li>';
	}
}
?>
</ul>
	<label><input type="checkbox" class="" name='__confirm'>Check to
		Confirm</label>
</div>

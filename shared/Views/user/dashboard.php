<?php
use System\Web\UI\Items\MenuItem;
if (!$items) {
	return;
}
?>
<div class="row">
	<div class="span8">

<?php
if (isset ( $items->center )) {
	echo '<ul class="thumbnails">';
	foreach ( $items->center as $v ) {
		echo '<li class="span1">';
		$action = BASE_URL . $v->action;
		$icon = null;
		if ($v->icon) {
			$icon = $appOwner->getLiveAsset ( $v->icon );
		}
		if (! $icon) {
			$icon = \URLHelper::add(ASSET_URL,'images/').'gear.png';
		}
		echo '<a class="thumbnail" href="' . $action . '">';
		echo '<img src="' . $icon . '">';
		echo '</a>';
		echo '<div class="caption">';
		echo '<h5>' . $v->title . '</h5>';
		if (isset ( $v->descr )) {
			echo '<p>' . $v->descr . '</p>';
		}
		echo '</div>';
		echo '</li>';
	}
	echo '</ul>';
}
?>
	</div>
</div>
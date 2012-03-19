<?php
echo '<div class="well">';
foreach ($rows as $row) {
	$info = $row->info;
	echo '<div class="row">';
	if ($info->valid) {
		echo '<div class="span1">';
		if ($info->imageURL) {
			echo '<img src="'.$info->imageURL.'" alt="profile picture"/>';
		}else{
			
		}
		echo '</div>';
		echo '<div>';
		echo '<span class="">'.$info->displayName.'</span>';		
		echo '<a href="'.$info->profileURL.'"  target="__blank">more..</a>';
		echo '</div>';
	}else{
		echo '<span class="label label-important">'.$info->_error.'</span>';
		echo '<a class="btn" href="'.$info->loginURL.'" >login</a>';
	}
	echo '</div>';
}
echo '<div class="button-group">';
echo '<a href="'.\URLHelper::add(APP_URL,'user/addexternal').'" class="btn btn-primary"><i class="icon icon-add"></i>Add</a>';
echo '</div>';
echo '</div>';
//pp($rows);
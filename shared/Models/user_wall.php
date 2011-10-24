<?php
class UserWallModel extends MVCModel {
	function __construct($connection) {
		parent::__construct($connection,'user_wall',array('user_id','wall_id'));
	}
}
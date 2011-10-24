
<div class="ui-helper-clearfix">
	<a> <img
		src="<?php echo BASE_URL.'/user/pic/?uid='.$row->user_id.'&w=50&h=50'?>" />
	</a>
	<div>
		<div class="action"></div>
		<h6>
			<a href="<?php echo BASE_URL.'/user/profile/?id='.$row->user_id?>">
			<?php echo $row->fullname?> </a>
			
			
			
			
			<?php echo $row->getLastStatus()?>
		</h6>
		<div>
			
			
		<?php echo $this->render('user/laststatus',true,false,array('row'=>$row,'statusid'=>$row->getLastStatusId()))?>
		</div>
	</div>
</div>

<?php
//pp($row);

?>

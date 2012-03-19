<div class="alert">
	<?php
	echo  '<strong>'.$lastError.'</strong>';	
	?>
	<div class="btn-group">
		<a href="" class="btn btn-warning">Retry</a>
		<a href="<?php echo \URLHelper::addParam($authurl,'mode=reset')?>" class="btn btn-danger">Reset</a>
	</div>
</div>

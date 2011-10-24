<?php if ($shared) {
	$dirc = $direct ? '-direct' : '';

?>
<div id="pub-share<?php echo $dirc ?>" class="pub-share"><?php if (!$direct) { ?>
	<img class="sh-button"
		src="<?php echo ASSET_URL ?>cgaf/images/plus.png" alt="Share" />
<?php }
?>
	<div class="share-content">
		<div>
		<?php echo $shared ?>
		</div>
	</div>
</div>
<?php } ?>
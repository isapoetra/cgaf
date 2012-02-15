<?php
use System\Web\Utils\HTMLUtils;
$id = 'share-' . time();
$url = isset($url) ? $url : NULL;
$title = isset($title) ? $title : null;
$services = isset($services) ? $services : null;
if (!$services) {
	return;
}
?>
<div id="<?php echo $id; ?>" class="share">
	<input id="txt-<?php echo $id; ?>" type="text" name="url"
		value="<?php echo $url; ?>" /> <input id="title-<?php echo $id; ?>"
		type="text" name="title" value="<?php echo $title; ?>" />
	<div class="services"><?php
						  foreach ($services as $s) {
							  echo HTMLUtils::renderButtonLink($s['url'], $s['title'], $s['attr'], $s['icon'], $s['descr']);
						  }
						  ?>
	</div>
</div>
<script type="text/javascript">
	$('#<?php echo $id ?> .services button').each(function(idx,e){
		$(this).bind('click',function(e){
			var txt = $('#txt-<?php echo $id ?>').val();
			var url = $(this).attr('url').toString().replace('{url}',txt);
			url = url.replace('{title}',$('#title-<?php echo $id ?>').val());
			window.open(url);
		});
	});
</script>

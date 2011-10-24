<?php
$sid = Utils::generateId('spr');
$js = Request::isSupport('javascript');
if (!$js) {
	echo HTMLUtils::beginForm(BASE_URL.'/person/search/');
}
echo HTMLUtils::renderTextBox('Search','q','',null,true);
$sc = <<< EOT
$('#$sid').slideDown(function(){
	var q = $(this).parent().find('#q').val();
	if (!q) {
		return false;
	}
	$(this).addClass('loading');
	$(this).load('/person/search/?_ajax=1&q='+q,function(e){
		$(this).removeClass('loading');
		$(this).html(e);
	});
});
EOT;
if ($js) {
	echo HTMLUtils::renderButton('button','Search','Search',array(
		'onclick'=>$sc
	));
}else{
	echo HTMLUtils::renderButton('submit','Search','Search');
}
if (!$js) {
	echo HTMLUtils::endForm(false,true);
}else{
	echo '<div id="'.$sid.'" style="display:none;min-height:100px;overflow:auto">&nbsp;</div>';
}
?>
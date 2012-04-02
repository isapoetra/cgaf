<?php 
use System\Web\Utils\HTMLUtils;

$ctl=$this->getController();
$formAction = isset($formAction) ? $formAction : \URLHelper::Add(APP_URL,$ctl->getControllerName().'/search');
$formId = isset($formId) ? $formId : \Utils::generateId('fs');
$moreAction = isset($moreAction) ? $moreAction : '';
if ($ctl->isAllow('search')) {
	$placeholder = isset($placeholder) ? $placeholder : __('search.placeholder');
	if ($moreAction) {
		$s =<<< EOS
var more = $('#$formId').find('.more').button();
var pSearch = new $.popupDialog(more,{
	contentEl:$('#$formId .search-more-container'),
});
$(pSearch).bind('close',function(){
	more.removeClass('active');
});
more.click(function(e){
	e.preventDefault();
	var b =!more.hasClass('active');
	if (b) {
		pSearch.show();
	}else{
		pSearch.hide();
	}
});
EOS;
		$appOwner->addClientScript($s);
	}
	echo '<div class="form-horizontal search-container">';
	echo HTMLUtils::beginForm($formAction,false,false,null,array('id'=>$formId,'class'=>'search-form'));
	?>
<div class="control-group">
	<div class="controls">
		<div class="input-append">
			<input class="span2 search-text required" id="q" name="q" type="text"
				placeholder="<?php echo $placeholder;?>" value="<?php echo @$q ? $q : @$row->q;?>">
			<?php if ($moreAction) {?>

			<button class="btn more" data-toggle="button">
				<span class="caret"></span>
			</button>
			<?php }?>
		</div>
		<button class="btn btn-primary" type="submit">Search</button>
	</div>
</div>
<?php
if ($moreAction) {
	echo '<div class="search-more-container">'.$moreAction.'</div>';
}
echo '<hr class="divider"/>';
echo HTMLUtils::endForm(false,true);
echo '</div>';

}
?>
<?php
use \Request;
use System\Web\Utils\HTMLUtils;
$cssClass= isset($cssClass) ? $cssClass : '';
$renderPrev= isset($renderPrev) ? $renderPrev : false;
$renderFormAction= isset($renderFormAction) ? $renderFormAction : true;
?>
<?php
echo HTMLUtils::beginForm(URLHelper::addParam(APP_URL,array('__c'=>'user','__a'=>'forgetpassword')), false,false,null,'id="forgot" class="'.$cssClass.'"');
echo HTMLUtils::renderTextBox(__('user.email'),'login',null,'clas="required email"',true);
?>
<label><?php echo __('birthdate','Birth Date')?> <input type="text"
	id="bd" name="bd" class="date" />&nbsp; <em><strong>format </strong>dd/mm/yyyy</em>
</label>



<?php
//echo HTMLUtils::renderCaptcha();
if (!$renderFormAction) {
	echo '<button type="submit">Request password</button>';
}

if ($renderPrev) {
	?>
<p>
	<a href="#" id="aprev" class="">Back?</a>
</p>

<?php
}
echo HTMLUtils::endForm($renderFormAction, true);
?>
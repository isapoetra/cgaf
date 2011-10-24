<?php
use System\Web\Utils\HTMLUtils;
use System\MVC\MVCHelper;
use System\Web\JS\CGAFJS;
CGAFJS::loadUI();
$row = isset($row) ? $row : new \stdClass();
$app = $this->getAppOwner();
$dateFormat = __('client.dateFormat','dd/mm/yyyy');
$actionurl = isset($actionurl) && $actionurl ? $actionurl : BASE_URL . "/user/register/";
$sc = <<<EOT
$('#register-form').gform({
	dateFormat:'$dateFormat'
});
$("#passStrength").progressbar({
	value: 0
});
EOT;
$app->addClientScript($sc);
echo HTMLUtils::beginForm($actionurl, false, false, null, array(
		'id' => 'register-form',
		'class' => 'ui-widget-content ui-corner-all'))
?>
<div id="error-message" class="error ui-clear-fix">&nbsp;</div>
<table cellpadding="0" cellspacing="0" border="0" width="100%">
	<tr>
		<th colspan="2" class="ui-widget-header"><?php echo __('user.logon.info') ?>
		</th>
	</tr>
	<tr>
		<td class="title" width="150px"><?php echo __('user.logon_name') ?>
		</td>
		<td><input type="text" name="user_name" id="user_name"
			class="required text" required value="<?php echo @$row->username ?>" />
			<em><?php echo __("user.logon_name.descr", "") ?> </em></td>
	</tr>
	<tr>
		<td class="title"><?php echo __('Password') ?></td>
		<td><input
			onkeypress="$('#passStrength').progressbar('option', 'value', $(this).passwordStrength($('#user_name').val()));"
			type="password" id="user_password" name="user_password"
			class="text required" maxlength="25" minlength="6" autocomplete="off" />
			<div id="passStrength" style="width: 150px; height: 15px"></div> <em><?php echo __('user.passstrength') ?>.</em>
		</td>
	</tr>
	<tr>
		<td class="title"><?php echo __('user.register.verifypassword') ?>
		</td>
		<td><input type="password" name="password1" class="text required"
			validator="{name:'eq',value:$('#register-form #user_password')}"
			autocomplete="off" /></td>
	</tr>




<?php if ($app->getConfig('user.register.profile', true)) { ?>
		<tr>
			<th colspan="2" class="ui-widget-header">Profile
			</th>
		</tr>
		<tr>
			<td class="title"><?php echo __('user.user_name', 'Name') ?></td>
			<td>
				<input type="text" name="first_name" class="required text2" value="<?php echo @$row->first_name ?>"/>
	    	<input type="text" name="middle_name" class="text2"  value="<?php echo @$row->middlename ?>"/>
	  		<input type="text" name="last_name" class="text2"  value="<?php echo @$row->last_name ?>"/>
			</td>
		</tr>
		<tr>
			<td class="title"><?php echo __('gender', 'Gender') ?></td>
			<td><?php echo HTMLUtils::renderSelect(null, 'gender', MVCHelper::lookup('gender','__cgaf'), @$row->gender,false) ?></td>
		</tr>
		<tr>
			<td class="title"><?php echo __('religion', 'Religion') ?></td>
			<td><?php echo HTMLUtils::renderSelect(null, 'religion', MVCHelper::lookup('religion','__cgaf'), @$row->religion,false) ?></td>
		</tr>
		<tr>
			<td class="title"><?php echo __('birthdate', 'Birth Date') ?></td>
			<td>
				<label>
				<input type="text" id="birth_date" name="birth_date" class="date" minYear="<?php echo date('Y') - (int) $this->getAppOwner()->getConfig("user.minage", 15) ?>" value="<?php echo @$row->birthdate ?>"/>&nbsp;
	    		<em><strong>format </strong><?php echo $dateFormat?></em> </label>
			</td>
		</tr>
		<tr>
			<td class="title"><?php echo __('address', 'Address') ?></td>
			<td>
				<textarea  name="address" class="required text"></textarea>
	  		</td>
		</tr>
<?php }
if ($app->getConfig('user.register.captcha', true)) {
			 ?>
		<tr>
			<td class="title"><?php echo __('verificationcode', 'Verification Code') ?></td>
			<td><?php echo HTMLUtils::renderCaptcha(null, null, false) ?></td>
		</tr>
<?php } ?>
		<tr>
			<td colspan="2" align="right"><?php echo sprintf(__('user.register.agree'), '<a href="' . BASE_URL . 'info/view/?type=register" class="agree" rel="__overlay">', '</a>') ?></td>
		</tr>
	</table>




<?php echo HTMLUtils::endForm(true, true); ?>

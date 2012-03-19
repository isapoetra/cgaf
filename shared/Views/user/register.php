<?php
use System\Web\Utils\HTMLUtils;
use System\MVC\MVCHelper;
use System\Web\JS\CGAFJS;

CGAFJS::loadUI();
$msg = isset($msg) ? $msg : array();
$row = isset($row) ? $row : new \stdClass();
$app = $this->getAppOwner();
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
                                                               'class' => 'well'));
echo '<h3 class="legend">' . __('user.logon.info') . '</h3>';
echo HTMLUtils::renderTextBox(__('user.user_name'), 'user_name', @$row->user_name, array(
                                                                                        'required' => 'required',
                                                                                        'class' => 'required' . (isset($msg['user_name']) ? ' error' : ' warning')
                                                                                   ));
echo HTMLUtils::renderPassword(__('user.user_password'), 'user_password', @$row->user_password, array(
                                                                                                     'required' => 'required',
                                                                                                     'max-length' => 25,
                                                                                                     'min-length' => 6,
                                                                                                     'autocomplete' => 'off',
                                                                                                     'onkeypress' => "$('#passStrength').progressbar('option', 'value', $(this).passwordStrength($('#user_name').val()));"

                                                                                                ), true, '<span class="help-inline">' . __('user.passstrength') . '</span><div id="passStrength" style="width: 150px; height: 15px"></div>');


echo HTMLUtils::renderPassword(__('user.register.verifypassword'), 'password1', null, array(
                                                                                           'validator' => "{name:'eq',value:$('#register-form #user_password')}"
                                                                                      ));
if ($app->getConfig('user.register.profile', true)) {
	echo '<h3 class="legend">' . __('user.profile', 'Profile') . '</h3>';
	?>
<table class="table">
	<tr>
		<th><?php echo __('user.user_name', 'Name');?></th>
		<td>
			<table class="table table-bordered">
				<tr>
					<th>First</th>
					<th>Middle</th>
					<th>Last</th>
				</tr>
				<tr>
					<td><input type="text" name="first_name" class="required text2" value="<?php echo @$row->first_name;?>"/></td>
					<td><input type="text" name="middle_name" class="text2" value="<?php echo @$row->middle_name;?>"/></td>
					<td><input type="text" name="last_name" class="text2" value="<?php echo @$row->last_name;?>"/></td>
				</tr>
			</table>
	</tr>
	<tr>
		<td class="title"><?php echo __('gender', 'Gender') ?></td>
		<td><?php echo HTMLUtils::renderSelect(null, 'gender', MVCHelper::lookup('gender', '__cgaf'), @$row->gender, false) ?></td>
	</tr>
	<tr>
		<td class="title"><?php echo __('religion', 'Religion') ?></td>
		<td><?php echo HTMLUtils::renderSelect(null, 'religion', MVCHelper::lookup('religion', '__cgaf'), @$row->religion, false) ?></td>
	</tr>
	<tr>
		<td class="title"><?php echo __('birthdate', 'Birth Date') ?></td>
		<td>
			<label>
				<input type="text" id="birth_date" name="birth_date" class="date"
				       minYear="<?php echo date('Y') - (int)$this->getAppOwner()->getConfig("user.minage", 15) ?>"
				       value="<?php echo @$row->birthdate ?>"/>&nbsp;
				<em><strong>format </strong><?php echo $dateFormat?></em> </label>
		</td>
	</tr>
	<tr>
		<td class="title"><?php echo __('address', 'Address') ?></td>
		<td>
			<textarea name="address" class="required text"></textarea>
		</td>
	</tr>
	<?php
}
if ($app->getConfig('user.register.captcha', true)) {
	?>
	<tr>
		<td class="title"><?php echo __('verificationcode', 'Verification Code') ?></td>
		<td><?php echo HTMLUtils::renderCaptcha(null, null, false) ?></td>
	</tr>
	<?php } ?>
	<tr>
		<td colspan="2"
		    align="right"><?php echo sprintf(__('user.register.agree'), '<a href="' . BASE_URL . 'info/view/?type=register" class="agree" rel="__overlay">', '</a>') ?></td>
	</tr>
</table>
<?php echo HTMLUtils::endForm(true, true); ?>

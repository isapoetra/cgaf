<?php
use System\Web\Utils\HTMLUtils;
echo HTMLUtils::beginForm ( BASE_URL . 'user/updateProfile/id/' . $data->user_id, false );
?>
<fieldset class="logonInfo">
	<legend>
<?php echo __("user.logoninfo","Logon Info")?></legend>
<?php
echo HTMLUtils::renderTextBox ( __ ( "user.name", "User Name" ), "user_name", $data->user_name, "", $edit );
if (! $_detail) {
	echo '<br/>';
	echo HTMLUtils::renderPassword ( __ ( "user.password", "Password" ), "user_password", $data->user_password, "", $edit );
	echo '<br/>';
	echo HTMLUtils::renderPassword ( __ ( "user.confirmpassword", "Password" ), "confirm_password", $data->user_password, "", $edit );
}
?>
	</fieldset>

<fieldset class="personInfo">

	<legend>



	<?php echo __("user.personalInfo","Personal Information")?></legend>
	<table>
		<tr>
			<td>
			<?php echo HTMLUtils::renderFormField(__("person.first_name","First Name"),"first_name",$personInfo->first_name,"",$edit)?>
			</td>
			<td>
			<?php echo HTMLUtils::renderFormField(__("person.middle_name","Middle Name"),"midle_name",$personInfo->middle_name,"",$edit)?>
			</td>
			<td>
			<?php echo HTMLUtils::renderFormField(__("person.last_name","Last Name"),"last_name",$personInfo->last_name,"",$edit)?>
			</td>
		</tr>
		<tr>
			<td colspan="3">

			<?php
			echo HTMLUtils::renderFormField ( __ ( "person.birth_date", "Birth Date" ), "birth_date", $personInfo->birth_date, "", $edit )?>
			</td>
		</tr>
		<tr>
			<td colspan="3">

			<?php
			echo HTMLUtils::renderTextBox ( __ ( "person.email", "Email" ), "email", $personInfo->email, "", $edit )?>
			</td>
		</tr>
	</table>
</fieldset>

<script type="text/javascript">
$(function() {
	$("#birth_date").datepicker({showOn: 'button', buttonImage: '<?php echo  ASSET_URL."calendar.gif"?>', buttonImageOnly: true});
});
</script>


<?php
echo HTMLUtils::endForm ( true, true, true );
?>
<?php
use System\Web\Utils\HTMLUtils;

$app = $this->getAppOwner();
$renderaction = isset($renderaction) ? $renderaction : true;
$mode = isset($mode) ? $mode : 'normal';
$uinfo = $app->getUserInfo($row->user_id);
?>
<table>
<?php
if ($renderaction) {
	   ?>
	<tr class="action" style="display: none">
		<td style="text-align: right;" colspan="2">
		<?php
	if ($uinfo->getConfig('addasfriend', true)) {
		echo HTMLUtils::renderButton('button', 'Add As Friend', 'Add this user as your friend', array('onclick' => '$.openOverlay({url:\'' . BASE_URL . '/person/af/?id=' . $row->user_id . '\'});return false;'));
	}
	if ($uinfo->getConfig('acceptmessagefromother', true)) {
		echo HTMLUtils::renderButton('button', 'Send Message', 'Send Personal Message', array('onclick' => ''));
	}
												   ?>
		</td>
	</tr>
<?php } ?>
	<tr>
		<td rowspan="3">
			<img src="<?php echo BASE_URL . 'person/image/?uid=' . $row->user_id . '&w=80&h=80' ?>"/>
		</td>
	</tr>
	<tr>
		<td valign="top" nowrap="nowrap"> <?php echo HTMLUtils::renderLink(BASE_URL . 'person/profile/?id=' . $row->person_id, $row->fullname) ?></td>
	</tr>
<?php
if ($mode !== 'addfriend') {
		 ?>
	<tr>
		<td nowrap="nowrap"  valign="top">
		<?php
	if ($uinfo->getConfig('publishbirthdate', CGAF_DEBUG)) {
		echo DateUtils::formatDate($row->birth_date) . ' - ' . DateUtils::ago(DateUtils::DateToUnixTime($row->birth_date));
	} else {
		echo '-';
	}
										  ?>
		</td>
	</tr>
<?php
} else {
	if (!$uinfo->isFriendOf(null) && $uinfo->getConfig('receivemessagefromother', CGAF_DEBUG)) {
		 ?>
	<tr>
		<td><?php echo HTMLUtils::renderTextArea('Message', 'message', '') ?>
		</td>
	</tr>
	<tr>
		<td colspan="2"><?php echo HTMLUtils::renderCaptcha('__userAddFriend') ?>
		</td>
	</tr>
<?php
	}
}
?>
</table>

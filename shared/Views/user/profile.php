<?php
use System\Web\Utils\HTMLUtils;
use System\Exceptions\AccessDeniedException;

$acl = $this->getAppOwner()->getACL();
$_detail = isset ($_detail) ? $_detail : false;
$edit = false;
if ($userInfo) {
	$edit = $acl->isAdmin() || $userInfo->isCurrentUser() && !$_detail;
}

$msg = isset ($message) ? $message : null;
$ov = Request::get('__overlay');
$tplVars = array(
	"edit" => $edit,
	"acl" => $acl,
	'_detail' => $_detail
);
if ($msg) {
	echo '<div id="message">' . $msg . '</div>';
}
?>
<div class="container">
<div class="span3">
	<table class="table table-striped table-bordered table-condensed">
		<tr>
			<?php
			$av = array(
				'user_name',
				'user_status',
				'date_created',
				'last_access',
				'last_ip');
			foreach ($av as $v) {
				$cl = 'label-info';
				$break = false;
				switch ($v) {
					case 'last_access':
						$break = 'last_from';
					case 'last_ip':
						$cl = 'label-warning';
						break;
				}
				$val = $userInfo->$v ? $userInfo->$v : '-';
				if ($break) {
					echo '<tr class="divider"><th colspan="2">' . __('user.' . $break) . '</th></tr>';
				}
				echo '<tr>';
				echo '<th>' . __('user.' . $v) . '</th>';
				echo '<td>' . $val . '</td>';
				echo '</tr>';
			}?>
		</tr>
	</table>
</div>
</div>

<?php
/**
 * @var $appOwner \System\MVC\Application
 */
echo $appOwner->renderContent('profile-bottom', null, false, true, array('mode'=>'thumb'), true);
?>


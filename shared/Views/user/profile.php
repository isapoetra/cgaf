<?php
use System\Web\Utils\HTMLUtils;
$acl = $this->getAppOwner()->getACL();
$_detail = isset($_detail) ? $_detail : false;
$edit = false;
if ($userInfo) {
	$edit = $acl->isAdmin() || $userInfo->isCurrentUser() && !$_detail;
}
$msg = isset($message) ? $message : null;
$ov = Request::get('__overlay');
$personInfo = $userInfo->getPerson();
$tplVars = array(
		"edit" => $edit,
		"person" => $personInfo,
		"acl" => $acl,
		'_detail' => $_detail);
//pp($personInfo);
function renderProfile($tpl, $name, $vars) {
	return $tpl->render("views/" . $name, true, false, $vars);
}
if ($view = Request::get("view")) {
	if (array_key_exists($view, $views)) {
		echo renderProfile($this, $view, $tplVars);
		return;
	}
	throw new AccessDeniedException();
}
?>
<div>
	<div class="profile-header">
		<div></div>
		<form action="<?php echo BASE_URL . 'person/search/' ?>">
			<input autocomplete="off" type="text" maxlength="256" name="q"
				label="Find People" placeholder="Find People">
		</form>
	</div>
	<div id="user-profile" class="user profile"

		 <?php echo $ov ? ' style="width:750px;height:450px;margin:10px;overflow:hidden"' : '' ?>>


<?php
//echo $this->render("usreg");
if ($msg) {
	echo '<div id="message">' . $msg . '</div>';
}
echo '<div class="profile-detail">';
if ($edit) {
	echo '<div role="button" class="btn-edit">' . __('user.profile.edit', 'Edit Profile') . '</div>';
}
if ($personInfo) {
	echo $this->render('profile/person', true, false, array(
					'userInfo' => $userInfo,
					'personInfo' => $personInfo));
}
																								  ?>
	</div>
	<div></div>
</div>

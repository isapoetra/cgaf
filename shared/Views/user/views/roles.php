<?php
$acl = $this->getAppOwner()->getACL();
$roles = $acl->getUserRoles(Request::get('id'));
?>
<h3>Assingned Roles</h3>
<ul>


<?php

foreach($roles as $role) {
	echo '<li>'.$role->role_name.'</li>';
}
?>
</ul>

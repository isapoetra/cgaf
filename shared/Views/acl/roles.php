<?php
use System\Web\UI\JQ\Grid;
use \Request;
use System\Web\UI\JQ\Accordion;
$columns = array("__action" => array('<a href="' . BASE_URL . '/acl/manage/?_a=userroles&role_id=#role_id#&app_id=#app_id#" rel="overlay">User</a>'));
$grd = new Grid('rolesgrid', $this, $model, $columns, BASE_URL . '/acl/manage/_a/roles/');
$grd->setAutoGenenerateColumn(true);
$grd->setBaseLang('roles');
if (!Request::isJSONRequest()) {
?>
<table>
	<tr>
		<td>
		<?php echo $grd->render(true); ?>
		</td>
		<td width="15%" valign="top">
		<?php
	$acc = new Accordion('aclrolesmenu', $this);
	$acitem = $acc->AddAccordion('Manage', '');
	foreach ($links as $link) {
		$acitem->addItem($link);
	}
	echo $acc->render(true);
									 ?>
		</td>
	</tr>
</table>
<?php } else {
	echo $grd->Render(true);
}
		?>
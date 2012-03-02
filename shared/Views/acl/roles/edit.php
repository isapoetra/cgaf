<?php
if (! defined ( "CGAF" ))
	die ( "Restricted Access" );
use System\Web\Utils\HTMLUtils;
/**
 * this file generatated with CGAF MVCGenerator
 * ---- Wed-Feb-2010 18:37:38------
 *
 * @category Views
 * @author Iwan Sapoetra
 * @copyright Cipta Graha Informatika Indonesia
 */
$backAction = isset ( $backAction ) ? $backAction : null;
if ($backAction && ! Request::get ( '__overlay' )) {
	echo "<div><a href=\"$backAction\">" . __ ( "Back" ) . "</div>";
}
echo HTMLUtils::beginForm ( BASE_URL . '/acl/manage/?_a=roles&_gridAction=store' );
?>
<ul>
	<li><?php echo HTMLUtils::renderFormField(__("roles.role_id","role_id"),"role_id",$row->role_id,array('class'=>'required number'),true);?>
	</li>
	<li><?php echo HTMLUtils::renderFormField(__("roles.app_id","app_id"),"app_id",$row->app_id ? $row->app_id : $this->getAppOwner()->getAppId(),null,true);?>
	</li>
	<li><?php echo HTMLUtils::renderFormField(__("roles.role_name","role_name"),"role_name",$row->role_name,array('class'=>'required'),true);?>
	</li>
	<li><?php echo HTMLUtils::renderFormField(__("roles.active","active"),"active",$row->active,null,true);?>
	</li>
</ul>

<?php
echo HTMLUtils::endForm ( true, true, true );
?>
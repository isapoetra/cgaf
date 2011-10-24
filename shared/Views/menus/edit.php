<?php
if (! defined("CGAF"))
die("Restricted Access");
/**
 * this file generatated with CGAF MVCGenerator
 * ---- Wed-Feb-2010 18:37:38------
 * @category Views
 * @author Iwan Sapoetra
 * @Copyright Cipta Graha Informatika Indonesia
 *
 **/
$backAction = isset($backAction) ? $backAction : null;
$dest = isset($dest) ? $dest : $this->getController()->getRouteName();
if ($backAction) {
	echo "<div><a href=\"$backAction\">".__("Back")."</div>";
}
echo HTMLUtils::beginForm(BASE_URL."/$dest/store/");
?>
<ul>
	<li><?php echo HTMLUtils::renderFormField(__("menus.menu_id","menu_id"),"menu_id",$row->menu_id,null,true);?>
	</li>
	<li><?php echo HTMLUtils::renderFormField(__("menus.app_id","app_id"),"app_id",$row->app_id,null,true);?>
	</li>
	<li><?php echo HTMLUtils::renderFormField(__("menus.caption","caption"),"caption",$row->caption,null,true);?>
	</li>
	<li><?php echo HTMLUtils::renderFormField(__("menus.menu_position","menu_position"),"menu_position",$row->menu_position,null,true);?>
	</li>
	<li><?php echo HTMLUtils::renderFormField(__("menus.menu_action_type","menu_action_type"),"menu_action_type",$row->menu_action_type,null,true);?>
	</li>
	<li><?php echo HTMLUtils::renderFormField(__("menus.menu_action","menu_action"),"menu_action",$row->menu_action,null,true);?>
	</li>
	<li><?php echo HTMLUtils::renderFormField(__("menus.menu_index","menu_index"),"menu_index",$row->menu_index,null,true);?>
	</li>
	<li><?php echo HTMLUtils::renderFormField(__("menus.menu_icon","menu_icon"),"menu_icon",$row->menu_icon,null,true);?>
	</li>
	<li><?php echo HTMLUtils::renderFormField(__("menus.tooltip","tooltip"),"tooltip",$row->tooltip,null,true);?>
	</li>
	<li><?php echo HTMLUtils::renderFormField(__("menus.menu_state","menu_state"),"menu_state",$row->menu_state,null,true);?>
	</li>
	<li><?php echo HTMLUtils::renderFormField(__("menus.xtarget","xtarget"),"xtarget",$row->xtarget,null,true);?>
	</li>
	<li><?php echo HTMLUtils::renderFormField(__("menus.menu_parent","menu_parent"),"menu_parent",$row->menu_parent,null,true);?>
	</li>
	<li><?php echo HTMLUtils::renderFormField(__("menus.menu_class","menu_class"),"menu_class",$row->menu_class,null,true);?>
	</li>
</ul>

<?php
echo HTMLUtils::endForm(true,true);?>
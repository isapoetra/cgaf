<?php

class MenuManager {
	protected static function getChildModuleMenu($m, $parent = 0) {
		$sql = "select * from #__modules_menu where mod_id='$m' and menu_parent='$parent' and menu_visible=1 order by menu_order";
		$mm = AppManager::getInstance() -> getModel('modules_menu');
		$mm -> setAlias('mm') -> clear();
		$mm -> select('mm.*,c.total');
		$mm -> where('mod_id=' . $m);
		$mm -> where('mm.menu_parent=' . $parent);
		$mm -> where('menu_visible=1');
		$mm -> orderBy('menu_order');
		$mm -> join('(select menu_parent,count(*) total from modules_menu group by menu_parent)', 'c', 'c.menu_parent=mm.menu_id');

		$retval = $mm -> loadObjects();
		return $retval;
	}

	public static function getModuleMenu($module, $parent = 0) {
		if(!ACLHelper::checkAppModule($module)) {
			if(CGAF_DEBUG)
				throw new AccessDeniedException();
			return false;
		}
		$lists = self::getChildModuleMenu($module, $parent);
		return $lists;
	}

}
?>
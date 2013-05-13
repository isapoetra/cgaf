<?php
use System\Exceptions\AccessDeniedException;
use System\ACL\ACLHelper;

class MenuManager {

	protected static function getChildModuleMenu($m, $parent = 0) {
		$sql = "select * from #__modules_menu where mod_id='$m' and menu_parent='$parent' and menu_visible=1 order by menu_order";
		/**
		 * @var \System\MVC\Model $mm;
		 */
		/** @noinspection PhpUndefinedMethodInspection */
		$mm = AppManager::getInstance()->getModel('modules_menu');
		$mm->setAlias('mm')->clear();
		$mm->select('mm.*,c.total');
		$mm->where('mod_id=' . $m);
		$mm->where('mm.menu_parent=' . $parent);
		$mm->where('menu_visible=1');
		$mm->orderBy('menu_order');
		$mm
				->join(
						'(select menu_parent,count(*) total from modules_menu group by menu_parent)',
						'c', 'c.menu_parent=mm.menu_id');
		$retval = $mm->loadObjects();
		return $retval;
	}

	public static function getModuleMenu($module, $parent = 0) {
		if (!ACLHelper::checkAppModule($module)) {
			if (CGAF_DEBUG)
				throw new AccessDeniedException();
			return false;
		}
		$lists = self::getChildModuleMenu($module, $parent);
		return $lists;
	}

	public static function addMenu($menu, $parent = 0) {
		$childs = null;
		
		$app = AppManager::getInstance();
		$mm = $app->getModel('menus');
		if (!isset($menu['app_id'])) {
			$menu['app_id'] = $app->getAppId();
		}
		if (!isset($menu['menu_id'])) {
			ppd($menu);
		}
		if (!isset($menu['menu_parent'])) {
			$menu['menu_parent'] = $parent;
		}
		$mm->newData();
		foreach ($menu as $k => $v) {
			if ($k !== 'childs') {
				$mm->$k = $v;
			} else {
				$menuidx = 0;
				foreach ($v as $kk => $vv) {
					if (!isset($v[$kk]['menu_index'])) {
						$v[$kk]['menu_index'] = $menuidx;
					}
				}
				$childs = $v;
			}
		}
		$id = $mm->store(false);
		if ($childs) {
			$p = $mm->menu_id;
			foreach ($childs as $c) {				
				self::addMenu($c, $p);
			}
			
		}
	}

	public static function add($args = array()) {
		$menuidx = 0;
		foreach ($args as $m) {
			if (!isset($m['menu_index'])) {
				$m['menu_index'] = $menuidx;
			}
			self::addMenu($m);
			$menuidx++;
		}
	}
}
?>
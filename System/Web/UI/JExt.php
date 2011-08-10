<?php
namespace System\Web\UI;
use System\Collections\Collection;
use \CGAF;
abstract class JExt {
	const ICON_ADD = "bnew";
	const ICON_EDIT = "bedit";
	const ICON_DELETE = "bdelete";
	const ICON_VIEW = "bview";
	const ICON_LIST = "blist";
	const ICON_PRINT = "x-rpt-print";
	const ICON_SAVE = "x-save";
	const ICON_CONFIGURE = "x-configure";
	const MENU_SEPARATOR = 'separator';
	const ICON_TRANSACTION = "x-transaction";
	const ICON_COPY = "x-copy";

	private static $_tb;
	private static $_tbb;
	public static function PluginURL() {
		return BASE_URL . '/assets/js/ext/ux/';
	}
	public static function isExtAllLoaded() {
		return AppManager::getInstance()->getConfig('app.js.ext.ext-all-loaded',true);
	}
	public static function loadPlugin($plugin) {
		if (is_array($plugin)) {
			$retval = array();
			foreach ($plugin as $p) {
				$retval[] = self::loadPlugin($p);
			}
			return $retval;
		}
		return CGAFJS::loadScript(self::PluginURL().$plugin);
	}
	public static function loadClass($className) {
		if (String::BeginWith ( $className, 'JExt' )) {
			$c = strtolower ( substr ( $className, 4 ) );
			return CGAF::Using ( 'System.Web.UI.Ext.' . $c );
		}
	}
	public static function addToolbar($obj, $id = null, $handler = null, $description = null, $icon = null, $type = 1) {
		if (is_string ( $obj )) {
			$obj = new MenuItem ( $obj, $id, 1, $handler, null, $description, $icon, $type );
		}
		self::$_tb [$obj->id] = $obj;
	}

	public static function GetToolbarByID($id) {
		foreach ( self::$_tb as $tb ) {
			if (is_object ( $tb ) && $tb->id == $id) {
				return true;
			}
		}
		return null;
	}
	public static function addBottomBar($obj, $id = null, $handler = null, $description = null, $icon = null, $type = 1) {
		if (is_string ( $obj )) {
			$obj = new MenuItem ( $obj, $id, 1, $handler, null, $description, $icon, $type );
		} elseif (is_array ( $obj )) {
			//multi ?
			if ($id) {
				foreach ( $obj as $v ) {
					self::addBottomBar ( $v, false );
				}
				return true;
			} else {
				$config = $obj;
				$obj = new MenuItem ();
				$obj->assign ( $config );
			}
		}
		self::$_tbb [$obj->id] = $obj;
		return true;
	}
}
?>
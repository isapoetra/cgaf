<?php
if (! defined ( "CGAF" ))
	die ( "Restricted Access" );
class JExtMenuItem extends JExtComponent {
	protected $_actionType;
	protected $_menuType;
	protected $_showCaption = true;
	protected $_beginGroup = false;
	protected $_module;
	protected $_iconPath;
	
	function __construct($id = null, $title = null, $menuType = 1, $handler = null, $link = null, $description = null, $icon = null, $action_type = 1) {
		parent::__construct ( null );
		$this->handler = $handler;
		$this->id = ($id ? $id : CGAF::genID ());
		$this->text = $title == "separator" ? $title : __ ( $title );
		$this->tooltip = $description;
		$this->link = $link;
		$this->iconCls = $icon;
		$this->_actionType = $action_type;
		$this->_menuType = $menuType;
	
		//$this->_config["menu"]= array ();
	}
	
	function setBeginGroup($value) {
		$this->_beginGroup = $value;
	}
	
	function setIconPath($value) {
		$this->_iconPath = $value;
	}
	
	function getIcon() {
		return $this->getConfig ( "icon" );
	}
	
	function setIcon($value) {
		if (! $value) {
			return false;
		}
		if (! CGAF::isLiveFile ( $value )) {
			// try to use small image first
			$fname = IOHelper::ExtractFileName ( $value ) . '-icon.' . IOHelper::extractFileExt ( $value );
			$path = $this->_iconPath . DS . "images" . DS;
			$found = false;
			if (is_file ( $path . $fname )) {
				$found = CGAF::LocaltoLive ( $path . $fname );
			} elseif (is_file ( $path . $value )) {
				$found = CGAF::LocaltoLive ( $path . $value );
			}
			if (! $found) {
				if (! $found = CGAF::FindImage ( $fname )) {
					$found = CGAF::FindImage ( $value );
				}
			}
		} else {
			$found = $value;
		}
		if ($found) {
			$this->setConfig ( "icon", $found );
		}
		return false;
	}
	
	function setModule($value) {
		$this->_module = $value;
	}
	
	function setToolTip($value) {
		if ($value) {
			$tooltip = $value;
			if (! is_array ( $tooltip )) {
				$tooltip = array (
					"text" => __ ( $value ), 
					"title" => __ ( 'Info' ) );
			}
			$this->setConfig ( "tooltip", $tooltip );
		}
	}
	
	function &addItem($obj, $key = null) {
		if (! isset ( $this->_config ["menu"] )) {
			$this->_config ["menu"] = array ();
		}
		if ($key !== null && is_string ( $key )) {
			$this->_config ["menu"] [$key] = $obj;
		} else {
			$this->_config ["menu"] [] = $obj;
		}
		return $obj;
	}
	
	function hasChild() {
		if (! isset ( $this->_config ["menu"] )) {
			return false;
		}
		return count ( $this->_config ["menu"] ) > 0;
	}
	
	function addEvent($eventName, $value) {
		$this->_event [$eventName] = $value;
	}
	
	function prepareConfigItem($k, & $v) {
		if ($k == 'text') {
			if (! $this->_showCaption && $this->iconCls) {
				$v = null;
			}
		}
	}
	
	function Render($return = false, & $handle = false) {
		$retval = "";
		if ($this->_beginGroup) {
			$handle = true;
			$retval = "\"separator\",";
		}
		if ($this->_id === JExt::MENU_SEPARATOR) {
			$handle = true;
			$retval .= "tb.addSeparator()"; //"'".EXT::MENU_SEPARATOR."'";
		} else {
			if ($this->_beginGroup) {
				$retval .= "{" . parent::Render ( $return, $handle ) . "}";
			} else {
				$retval .= parent::Render ( $return, $handle );
			}
		}
		if (! $retval) {
			Response::Write ( $retval );
		}
		return $retval;
	}
	
	function getEvents() {
		return $this->_event;
	}
	
	function assign($obj, $val = null) {
		
		if (is_object ( $obj )) {
			$this->id = "mm_" . $obj->mod_id;
			$this->text = __ ( $obj->mod_ui_name );
			$this->description = __ ( $obj->mod_description );
			//$item->img= $module->mod_ui_icon; //GUI::findImage($module->mod_ui_icon,$module->mod_directory);
			$this->module = $obj->mod_id;
			$this->action_type = 1;
			//$item->icon = $module->mod_ui_icon;
			$this->link = "_m=" . $obj->mod_id;
			$this->handler = "function() {doLoadPageContent('" . $this->link . "','" . $obj->mod_dir . "','" . $this->text . "');}";
		} elseif (is_array ( $obj )) {
			$this->setConfig ( $obj );
		} else {
			parent::assign ( $obj );
		}
	}
}
class TExtMenu extends JExtComponent {
	
	function __construct() {
		parent::__construct ( "GExt.controls.Toolbar" );
	}
	
	protected function getChildMenu($mod_id, $parent) {
		$sql = "select * from #__modules_menu where mod_id='$mod_id' and menu_parent=$parent and menu_visible=1 order by menu_order";
		return DB::loadObjectLists ( $sql );
	}
	
	function loadChildMenu(& $item, $module, $parent) {
		if (! ACL::checkModule ( $module )) {
			return false;
		}
		$crows = $this->getChildMenu ( $module, $parent );
		if (count ( $crows ) > 0) {
			$minfo = ModuleManager::getModuleInfo ( $module );
			foreach ( $crows as $row ) {
				$c = new JExtMenuItem (); 
				$c->id = "m_" . $row->menu_id;
				$c->text = __ ( $row->menu_caption );
				$c->iconPath = $minfo->mod_path;
				$c->icon = $row->menu_images; //$row->menu_images; //GUI::findImage($module->mod_ui_icon,$module->mod_directory);
				$c->module = $module;
				$c->action_type = $row->menu_action;
				$c->parent = $row->menu_parent;
				$c->description = $row->menu_description;
				$c->link = (stristr ( $row->menu_link, "_m=" ) ? "" : "_m=$module&") . $row->menu_link;
				$out = array ();
				parse_str ( $c->link, $out );
				$mp = isset ( $out ["_m"] ) ? $out ["_m"] : $module;
				switch ($row->menu_action) {
					case 2 :
						$c->handler = "function() {doCreateRemoteWindow({autoLoad:'./?" . $c->link . "'" . ($row->config ? "," . $row->config : "") . "},null,{changed:Ext.emptyFn});}";
						break;
					default :
						$c->handler = "function() {doLoadPageContent('" . $c->link . "','" . $mp . "','" . $c->text . "');}";
						break;
				}
				$c->setBeginGroup ( $row->menu_begin_group );
				$cc = $this->getChildMenu ( $module, $row->menu_id );
				if (count ( $cc ) > 0) {
					$this->loadChildMenu ( $c, $module, $row->menu_id );
				}
				$item->addItem ( $c );
			
		//$item->childs[]= $c;
			}
		}
		return true;
	}
	
	function loadAppMenu($app_id = null) {
		if (! $this->items) {
			$this->items = array ();
		}
		if (! $app_id) {
			$app_id = CGAF::getInstance ()->AppInfo->app_id;
		}
		$sql = "SELECT * FROM #__modules WHERE mod_active > 0 AND mod_ui_active > 0 ";
		$sql .= "and mod_app_owner=$app_id and mod_ui_active=1";
		$sql .= " ORDER BY mod_order,mod_ui_order";
		$nav = DB::loadObjectLists ( $sql );
		foreach ( $nav as $module ) {
			$this->assignModule ( $module->mod_id );
		}
		$mods = ModuleManager::getInternalModules ();
		foreach ( $mods as $module ) {
			$this->assignModule ( $module->mod_id );
		}
	}
	
	function assignModule($module) {
		$module = ModuleManager::getModuleInfo ( $module );
		//pp($module);
		if ($module && $module->mod_active && $module->mod_ui_active) {
			$item = new JExtMenuItem ();
			$item->id = "mm_" . $module->mod_dir;
			$item->text = __ ( $module->mod_ui_name );
			$item->tooltip = __ ( $module->mod_description );
			//$item->img= $module->mod_ui_icon; //GUI::findImage($module->mod_ui_icon,$module->mod_directory);
			$item->module = $module->mod_id;
			$item->action_type = 1;
			//$item->icon = $module->mod_ui_icon;
			$item->link = "_m=" . $module->mod_id;
			$item->handler = "function() {doLoadPageContent('" . $item->link . "','" . $module->mod_id . "','" . $item->text . "');}";
			$icon = $module->menu_icon ? $module->menu_icon : $module->live_icon;
			$item->icon = $icon;
			if ($item->icon) {
				//$item->icon= $module->menu_icon;
				$item->cls = "x-btn-text-icon";
			}
			$this->loadChildMenu ( $item, $module->mod_id, 0 );
			if ($item->hasChild ()) {
				$item->handler = null;
			}
			$this->addItem ( $item );
		}
	}
	
	function RenderTable($item, $imgprops = "width=\"48\" height=\"48\"") {
		return MenuManager::RenderMenuAsTable ( $item, $imgprops );
	}

	//eof class 
}
class ExtMenu {
	
	public static function RenderModuleMenu($m, $app = null, $return = false) {
		//throw new TSystemException("hi");
		if (! $app) {
			$app = AppManager::getInstance ();
		}
		$menulist = $app->getMenuModule ( $m );
		$menu = new TExtMenu ();
		$retval = $menu->RenderTable ( $menulist );
		if (ACL::checkmodule ( $m, "add" )) {
			$retval .= "<a href=\"?_m=devtools&_u=menu&_a=addedit&module=$m\">New</a>";
		}
		if (! $return) {
			echo $retval;
		}
		return $retval;
	}
}
?>
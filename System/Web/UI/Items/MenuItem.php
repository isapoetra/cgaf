<?php
namespace System\Web\UI\Items;
use System\Web\UI\Controls\Menu;
use System\Web\UI\Controls\WebControl;
use System\Web\Utils\HTMLUtils;
use AppManager;
use Strings;
use System\Web\UI\Controls\Anchor;
use IRenderable;
class MenuItem extends WebControl {
	private $_selected;
	private $_link;
	private $_actionType = 1;
	private $_tooltip;
	private $_icon;
	private $_showCaption;
	private $_showIcon = true;
	private $_iconClass;
	private $_replacer = array (
			'appurl' => APP_URL
	);
	private $_menu;
	function __construct($id, $title = null, $action = null, $selected = false, $tooltip = null, $xtarget = null, $showIcon = true, $attrs = array(), $icon = null) {
		parent::__construct ( 'li', false, $attrs );
		$this->_link = new Anchor ( '#', '' );
		$this->_menu = new Menu ();
		if (is_array ( $id ) || is_object($id)) {
			if (is_array($id)) {
				$id = \Utils::bindToObject ( new \stdClass (), $id, true );
			}
			if ($id instanceof MenuItem) {
				ppd ( $id );
			} else {
				isset ( $id->xtarget ) ? $this->_link->setAttr ( 'target', $id->xtarget ) : null;
				$this->id = isset ( $id->menu_id ) ? $id->menu_id : \Utils::generateId ( 'menu' );
				$this->Text = isset ( $id->caption ) ? $id->caption : (isset ( $id->title ) ? $id->title : null);
				$this->Action = isset ( $id->menu_action ) ? $id->menu_action : null;
				$this->Selected = isset ( $id->selected ) ? $id->selected : false;
				$this->Icon = isset ( $id->menu_icon ) ? $id->menu_icon : $this->_icon;
				$this->_actionType = isset ( $id->menu_action_type ) ? ( int ) $id->menu_action_type : $this->_actionType;
				$this->Tooltip = isset ( $id->tooltip ) ? $id->tooltip : $this->_title;
				isset ( $id->menu_class ) ? $this->setAttr ( 'style', $id->menu_class ) : null;
				isset ( $id->attrs) ? $this->setAttr($id->attrs):null;
				//isset ( $id->attrs) ? ppd($this) : '';
			}
		}else {
			$this->Id = $id;
			$this->Text = $title;
			$this->Action = $action;
			$this->Selected = $selected;
			$this->Tooltip = $tooltip;
			$this->ShowCaption = $showIcon;
			$this->TargetLink = $xtarget;
			$this->Icon = $icon;
		}
	}
	function setAction($a) {
		$this->_link->setAction ( $a );
	}
	function setDescr($val) {
		$this->Tooltip = $val;
	}
	function getAction() {
		return $this->_link->getAction ();
	}
	function getActionType() {
		return $this->_actionType;
	}
	function setText($text) {
		$this->_link->setText ( $text );
	}
	function setReplacer($r) {
		if ($r) {
			if (is_array ( $r ) || is_object ( $r )) {
				foreach ( $r as $k => $v ) {
					$this->_replacer [$k] = $v;
				}
			} else {
				$this->_replacer = $r;
			}
		}
	}
	function setSelected($value) {
		if ($value) {
			$this->addClass ( 'active' );
		} else {
			$this->removeClass ( 'active' );
		}
	}
	function setShowIcon($value) {
		$this->_showIcon=$value;
	}
	function setIcon($v) {
		$this->_icon = $v;
	}

	private function parselink($link) {
		$this->replace ( $link );
		switch ($this->_actionType) {
			case "2" :
				$link=\URLHelper::add (APP_URL,$link);
				break;
				// action based on mvc action
			case "1" :
			default :
				if (! Strings::BeginWith ( $link, "http:" ) && ! Strings::BeginWith ( $link, "https:" ) && ! Strings::BeginWith ( $link, "javascript:" )) {
					$link = \URLHelper::add ( APP_URL, $link );
				}
		}
		return $link;
	}
	function addChild($c) {
		if ($c instanceof MenuItem) {
			$this->_menu->addClass ( 'dropdown-menu' );
		}
		return $this->_menu->addChild ( $c );
	}
	function setChilds($items) {
		$this->_menu->addChild ( $items );
	}
	protected function replace(&$text) {
		$r = $this->_replacer;
		if ($this->getParent () instanceof Menu) {
			$rp = $this->getParent ()->getReplacer ();
			foreach ( $rp as $k => $v ) {
				$r [$k] = $v;
			}
		}
		if ($r) {
			foreach ( $r as $k => $v ) {
				$text = str_ireplace ( '#' . $k . '#', $v, $text );
			}
		}
		return $text;
	}
	function setIconClass($c) {
		$this->_showIcon=true;
		$this->_iconClass=$c;
	}
	function prepareRender() {
		parent::prepareRender ();
		$hasChild = $this->_menu->hasChild ();
		if ($this->_link->Text === '-') {
			$this->addClass ( 'divider' );
			$this->_childs = array ();
			return;
		}
		$this->_childs = array ();
		if ($hasChild) {
			$this->addClass ( 'dropdown' );
			$this->_link->AddClass ( 'dropdown-toggle' );
			$this->_link->setattr ( 'data-toggle', 'dropdown' );
			$this->_link->addChild ( '<b class="caret"></b>' );
		}else{
			if (!\Request::isMobile()) {
				$this->_link->setattr2 (array(
						'rel'=>'external',
						'data-transition'=>'pop'));
			}
		}
		$title = __ ( $this->_link->Text );
		$this->replace ( $title );

		$this->_link->Action = $this->parseLink ( $this->_link->Action );
		$this->replace ( $this->_tooltip );
		if ($this->_showIcon && $this->_icon) {
			$icon = AppManager::getInstance ()->getLiveAsset ( $this->_icon, 'images' );
			if (!$icon) {
				$icon = BASE_URL.'/assets/images/blank.png';
			}
			$this->_link->addChild ( '<img src="' . $icon . '"/>' );
		}elseif ($this->_showIcon && $this->_iconClass) {
			$title = '<i class="'.$this->_iconClass.'"></i>'.$title;
		}
		$this->_link->Text = $title;
		$this->_childs [] = $this->_link;
		if ($hasChild) {
			$this->_menu->setClass ( 'dropdown-menu' );
			$this->_childs [] = $this->_menu;
		}
		if (\Request::isMobile ()) {
			// full refresh
			$this->_link->setAttr ( 'rel', 'external' );
		}
	}
}
?>

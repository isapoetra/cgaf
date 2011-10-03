<?php
//TODO moved to MVCModel
namespace System\Collections\Items;
use \AppManager;
use \String;
use \System\Web\UI\Controls\Anchor;
class MenuItem extends \Object implements \IRenderable {
	private $_id;
	private $_title;
	private $_action;
	private $_selected = false;
	private $_icon = null;
	private $_actionType = 1;
	private $_showCaption = true;
	private $_showIcon = true;
	private $_targetLink = "#maincontent";
	private $_tooltip;
	private $_childs = array ();
	private $_css;
	private $_descr;
	private $_replacer = array (

	);
	function __construct($id, $title = null, $action = null, $selected = false, $tooltip = null, $xtarget = null, $showIcon = true) {
		parent::__construct ();
		if (is_array ( $id ))
		$id = Utils::bindToObject ( new stdClass (), $id, true );

		if (is_object ( $id )) {
			if ($id instanceof MenuItem) {
				$id->AssignTo ( $this );
			} else {
				$this->_targetLink = isset ( $id->xtarget ) ? $id->xtarget : null;
				$this->_id = isset ( $id->menu_id ) ? $id->menu_id : Utils::generateId ( 'menu' );
				$this->Title = isset ( $id->caption ) ? $id->caption : (isset($id->title) ? $id->title : null);
				$this->_action = isset ( $id->menu_action ) ? $id->menu_action : null;
				$this->_selected = isset ( $id->selected ) ? $id->selected : false;
				$this->_icon = isset($id->menu_icon) ? $id->menu_icon :  $this->_icon;
				$this->_actionType = isset ( $id->menu_action_type ) ? ( int ) $id->menu_action_type : $this->_actionType;
				$this->_tooltip = isset ( $id->tooltip ) ? $id->tooltip : $this->_title;
				$this->_css = isset ( $id->menu_class ) ? $id->menu_class : null;
			}

		} else {
			$this->_id = $id;
			$this->_title = $title;
			$this->_action = $action;
			$this->_selected = $selected;
			$this->_tooltip = $tooltip;
			$this->_showCaption = $showIcon;
			$this->_targetLink = $xtarget;
		}
		$this->_icon = $this->_icon ? $this->_icon : null;
		$this->_replacer=array('appurl'=>APP_URL);
	}
	function setChilds($childs) {
		$this->_childs = $childs;
		return $this;
	}
	function setCss($value) {
		$this->_css = $value;
		return $this;
	}
	function getChilds() {
		return $this->_childs;
	}
	function getActionType() {
		return $this->_actionType;
	}
	function addChild($c) {
		$this->_childs [] = $c;
		return $this;
	}
	function setDescr($value) {
		$this->_descr = $value;
	}
	function getDescr() {
		return $this->_descr;
	}
	function getId() {
		return $this->_id;
	}
	function getMenuAction() {
		return $this->_action;
	}
	function setTitle($value) {
		$this->_title = __($value);
		return $this;
	}
	function getTitle() {
		return $this->_title;
	}
	function setMenuAction($act) {
		$this->_action = $act;
	}
	function setShowIcon($value) {
		$this->_showIcon = $value;
	}
	function getIcon() {
		return $this->_icon;
	}
	function setIcon($icon) {
		$this->_icon = $icon;
	}
	function setSelected($flag) {
		$this->_selected = $flag;
	}
	function getTooltip() {
		return $this->_tooltip;
	}
	function renderChilds($includediv = true) {
		if (! count ( $this->childs )) {
			return "";
		}
		$retval = "";
		if ($includediv) {
			$retval .= '<div class="sub">';

			$retval .= '<ul>';
		}
		foreach ( $this->_childs as $child ) {
			if ($child instanceof IRenderable) {
				$retval .= $child->render ( true );
			} elseif (is_string ( $child )) {
				$retval .= '<li>' . $child . "</li>";

			}
		}

		if ($includediv) {
			$retval .= '</ul>';
			$retval .= '</div>';
		}
		return $retval;
	}
	public function setReplacer($r) {
		$this->_replacer = $r;
	}
	private function parselink($link) {
		if ($this->_replacer) {
			foreach ( $this->_replacer as $k => $v ) {
				$link = str_ireplace ( '#' . $k . '#', $v, $link );
			}
		}
		switch ($this->_actionType) {
			case "2" :
				break;
				//action based on mvc action
			case "1" :
			default :
				if (! String::BeginWith ( $link, "http:" ) && ! String::BeginWith ( $link, "https:" )) {
					$link = BASE_URL . $link;
				}
		}


		return $link;
	}
	function loadFromFile($f) {
		$f = Utils::toDirectory ( $f );
		if (! is_file ( $f )) {
			return;
		}
		ppd ( $f );
	}
	function Render($return = false) {
		if ($this->_title ==='-') {
			return '<li class="divider"></li>';
		}
		$class = $this->_selected ? "selected" : "";
		$action = "";
		$class = $class || $this->_css ? "class=\"{$this->_css} $class\"" : '';
		$retval = '';
		$retval = "<li " . $class . "$action id=\"{$this->_id}\">";
		$icon = null;
		if ($this->_showIcon) {
			$icon = AppManager::getInstance ()->getLiveData ( $this->_icon, 'images' );

			if ($icon) {
				$icon = "<img src=\"" . $icon . "\"/>";
			} elseif ($this->_icon && GAF_DEBUG) {
				$icon = '<div class="warning" style="display:none">icon ' . $this->_icon . 'not found</div>';
			}
		}

		$caption = $icon .'<span class="tdl '.($this->_selected ? "selected" : "").'">'. ($this->_showCaption ? '<span>' . __ ( $this->_title ) . '</span>' : "").'</span>';
		$act = $this->parseLink ( $this->_action );
		$r = new Anchor ( $act, $caption, $this->_targetLink, __ ( $this->_tooltip ) );
		$r->setClass ( $this->_css . ' ' .  (count ( $this->_childs ) ? ' menuparent' : '') );
		$retval .= $r->Render ( true );
		$retval .= $this->renderChilds ();
		$retval .= "</li>";
		if (! $return) {
			Response::write ( $retval );
		}
		return $retval;
	}
}
?>

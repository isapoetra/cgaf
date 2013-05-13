<?php
namespace System\UI\Items;

use Utils;
use System\Web\UI\Items\MenuItem;
/**
 * 
 * @author e1
 *@deprecated
 */
abstract class MenuItemx extends \BaseObject implements \IRenderable {
	protected $_id;
	protected $_title;
	protected $_action;
	protected $_selected = false;
	protected $_icon = null;
	protected $_actionType = 1;
	protected $_showCaption = true;
	protected $_showIcon = true;
	protected $_targetLink = "#maincontent";
	protected $_tooltip;
	protected $_childs = array ();
	protected $_css;
	protected $_descr;
	protected $_replacer = array ();
	function __construct($id, $title = null, $action = null, $selected = false, $tooltip = null, $xtarget = null, $showIcon = true) {
		parent::__construct ();
		if (is_array ( $id ))
			$id = Utils::bindToObject ( new \stdClass (), $id, true );
		if (is_object ( $id )) {
			if ($id instanceof MenuItem) {
				$id->AssignTo ( $this );
			} else {
				$this->_targetLink = isset ( $id->xtarget ) ? $id->xtarget : null;
				$this->_id = isset ( $id->menu_id ) ? $id->menu_id : Utils::generateId ( 'menu' );
				$this->Title = isset ( $id->caption ) ? $id->caption : (isset ( $id->title ) ? $id->title : null);
				$this->_action = isset ( $id->menu_action ) ? $id->menu_action : null;
				$this->_selected = isset ( $id->selected ) ? $id->selected : false;
				$this->_icon = isset ( $id->menu_icon ) ? $id->menu_icon : $this->_icon;
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
		$this->_replacer = array (
				'appurl' => APP_URL 
		);
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
		$this->_title = __ ( $value );
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
	function setShowCaption($value) {
		$this->_showCaption = $value;
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
			// $retval .= '<div class="sub">';
			$retval .= '<ul class="dropdown-menu" role="menu" aria-labelledby="dLabel">';
			
		}
		foreach ( $this->_childs as $child ) {
			
			if (is_object ( $child )) {				
				$retval .= \Convert::toString ( $child );			
			} elseif (is_string ( $child )) {
				$retval .= ($includediv ? '<li>xxx' : '') . $child . ($includediv ? '</li>' : '');
			}
		}
		if ($includediv) {
			$retval .= '</ul>';
			// $retval .= '</div>';
		}
		return $retval;
	}
	public function setReplacer($r) {
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
	protected function replace(&$text) {
		if ($this->_replacer) {
			foreach ( $this->_replacer as $k => $v ) {
				$text = str_ireplace ( '#' . $k . '#', $v, $text );
			}
		}
		return $text;
	}
	
	function loadFromFile($f) {
		$f = Utils::toDirectory ( $f );
		if (! is_file ( $f )) {
			return;
		}
		ppd ( $f );
	}

}
?>

<?php
namespace System\Web\UI\Items;
use System\Web\Utils\HTMLUtils;
use \AppManager;
use \String;
use \System\Web\UI\Controls\Anchor;
use \IRenderable;
class MenuItem extends \System\UI\Items\MenuItem {
	private $_attrs;
	private function parselink($link) {
		$this->replace($link);
		switch ($this->_actionType) {
		case "2":
			break;
		//action based on mvc action
		case "1":
		default:
			if (!String::BeginWith($link, "http:") && !String::BeginWith($link, "https:")) {
				$link = \URLHelper::add(APP_URL, $link);
			}
		}
		return $link;
	}
	function setAttrs($attrs) {
		$this->_attrs = $attrs;
	}
	function Render($return = false) {
		if ($this->_title === '-') {
			return '<li class="divider"></li>';
		}
		$class = 'menu-item-' . $this->_id . ' menu-item' . ($this->_selected ? " selected" : "");
		$action = "";
		$class = $class || $this->_css ? "class=\"{$this->_css} $class\"" : '';
		$retval = '';
		$retval = "<li $class $action id=\"{$this->_id}\" " . HTMLUtils::renderAttr($this->_attrs) . '>';
		$icon = null;
		if ($this->_showIcon) {
			$icon = AppManager::getInstance()->getLiveData($this->_icon, 'images');
			if ($icon) {
				$icon = "<img src=\"" . $icon . "\"/>";
			} elseif ($this->_icon && GAF_DEBUG) {
				$icon = '<div class="warning" style="display:none">icon ' . $this->_icon . 'not found</div>';
			}
		}
		$title = __($this->_title);
		$this->replace($title);
		$this->replace($this->_tooltip);
		$caption = $icon . '<span class="tdl ' . ($this->_selected ? "selected" : "") . '">' . ($this->_showCaption ? '<span>' . $title . '</span>' : "") . '</span>';
		$act = $this->parseLink($this->_action);
		$r = new Anchor($act, $caption, $this->_targetLink, __($this->_tooltip));
		$r->setClass($this->_css . ' ' . (count($this->_childs) ? ' menuparent' : ''));
		$retval .= $r->Render(true);
		if (count($this->childs)) {
			$retval .= '<span class="arrow"></span>';
		}
		$retval .= $this->renderChilds();
		$retval .= "</li>";
		if (!$return) {
			Response::write($retval);
		}
		return $retval;
	}
}
?>

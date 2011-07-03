<?php
if (! defined("CGAF"))
	die("Restricted Access");
class WebLink extends WebControl implements IRenderable {
	private $_link;
	private $_target;
	private $_title;
	private $_jsMode = true;
	private $_tooltip;
	private $_additionaljs;
	private $_class;
	function __construct($link, $title, $target=null,$tooltip=null, $jsMode = true,$additionaljs=null) {

		$this->_link = $link;
		$this->_target = $target;
		$this->_title = $title;
		$this->_jsMode = $jsMode;
		$this->_tooltip = $tooltip;
		$this->_additionaljs=$additionaljs;
		$attr =array();
		if ($tooltip) {
			$attr['title'] = $tooltip;
		}
		if ($target) {
			$attr['rel'] = $target;
		}

		$attr['href'] = $link;
		$this->setText($title);
		parent::__construct('a',true,$attr);
	}

	function setClass($class) {
		$this->_class =$class;
	}
	/*function Render($return = false) {
		if ($this->_jsMode && $this->_target) {
			$action = 'onclick=\'javascript:$("' . $this->_target . '").addClass("loading").html("loading...").slideToggle("slow").'.($this->_additionaljs ? ".". $this->_additionaljs :"").'.load("' . $this->_link . '?_ajax=1")\'';
			//$retval = " <a href=\"javascript:void(0);\" title=\"{$this->_tooltip}\" $action>" . _($this->_title) . "</a>";
		} else {

		}
		if ($this->_jsMode && $this->_target) {
			if (strpos($this->_link,"?")==0) {
				//$this->_link.="?_ajax=1";
			}
		}
		$class = $this->_class ? "class=\"$this->_class\"" : "";
		$retval = "<a href=\"{$this->_link}\" $class ".(empty($this->_tooltip) ? "" : " title=\"{$this->_tooltip}\"").($this->_target ? ' xtarget="'.$this->_target.'"' : '' )." $this->_additionaljs>" . $this->_title . "</a>";
		if (! $return) {
			Response::Write($retval);
		}
		return $retval;
	}*/

	public static function link($link, $title, $target,$tooltip, $jsMode = true,$additionaljs=null) {
		$link = new WebLink($link, $title, $target,$tooltip, $jsMode,$additionaljs);
		return $link->render(true);
	}
}

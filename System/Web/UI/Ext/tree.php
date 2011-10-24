<?php
namespace System\Web\UI\Ext;
use System\Web\UI\Ext\Tree\Panel as TreePanel;
class Tree extends TreePanel {
	function Render($return = false, &$handle = false) {
		$retval = $this -> preRender();
		$retval .= $this -> renderConfig();
		if($this -> _class) {
			$handle = true;
			$retval = "new $this->_class({" . $retval . "})";
		}
		if(!$return) {
			Response::Write($retval);
		}
		return $retval;
	}

}
?>
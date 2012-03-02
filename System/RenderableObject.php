<?php
class RenderableObject extends \BaseObject implements  \IRenderable {
	function __construct($o=null) {
		$this->bind($o);		
	}
	protected function isAllowBind($prop,$value) {
		return true;
	}
	protected function bind($o) {
		if (!$o) {
			return $this;
		}
		if (is_array($o) || is_object($o)) {

			foreach ($o as $k=>$v) {
				if ($this->isAllowBind($k,$v)) {
					$this->{$k} = $v;
				}
			}
		}
		return $this;
	}
	protected function renderInternal($return) {

	}
/**
	 * @param unknown_type unknown_type unknown_type unknown_type $return
	 */
	public function Render($return = false) {
		return $this->renderInternal($return);
	}

}
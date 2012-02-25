<?php
namespace System\Collections;

use System\Web\UI\Controls\HTMLControl;

class ControlCollection extends Collection {
	
	function Render(IWriter $writer) {
		foreach ( $this->Items as $v ) {
			$v->Render ( $writer );
		}
	}
	
	public function add($item, $multi = false) {
		$idx = parent::add ( $item );
		return $this->itemAt ( $idx );
	}
	
	function getControlByTagName($tagName) {
		foreach ( $this->Items as $v ) {
			if ($v instanceof HTMLControl) {
				if (strcasecmp ( $v->tagName, $tagName ) == 0) {
					return $v;
					break;
				}
			}
		}
	}
}
?>
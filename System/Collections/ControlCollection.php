<?php
namespace System\Collections;

use System\Web\UI\Controls\HTMLControl;

class ControlCollection extends Collection {
	
	function Render($return =true) {
        $retval = '';
        /**
         * @var \IRenderable $v
         */
        foreach ( $this->Items as $v ) {
			$retval .= $v->Render ( true );
		}
        if (!$return) {
            \Response::write($retval);
        }
        return $retval;
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
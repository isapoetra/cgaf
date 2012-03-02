<?php
/**
 * @deprecated
 */
class TSmartObject {
	function __construct($ref) {
		$reference = new ReflectionClass($ref);
		$props = $reference->getProperties();
		foreach ( $props as $v ) {
			if (Strings::BeginWith($v->name,"_")) {
				continue;
			}
			$this->{$v->name} = null;
		}
	}
}
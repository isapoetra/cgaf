<?php
class TSmartObject {
	function __construct($ref) {
		$reference = new ReflectionClass($ref);
		$props = $reference->getProperties();

		foreach ( $props as $v ) {
			if (String::BeginWith($v->name,"_")) {
				continue;
			}
			$this->{$v->name} = null;
		}
	}
}
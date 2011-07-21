<?php
defined ( "CGAF" ) or die ( "Restricted Access" );

abstract class Convert {
	
	public static function toBoolean($o) {
		$t = strtolower ( gettype ( $o ) );
		
		switch ($t) {
			case 'boolean' :
				return $o;
				break;
			case 'number' :
				break;
			case 'string' :
				switch (strtolower ( $o )) {
					case 'no' :
					case '0' :
					case 'false' :
						return false;
					case '1' :
					case 'true' :
					case 'yes' :
					default :
						return true;
				}
				break;
		
		}
		return ( boolean ) $o;
	
	}
	public static function toBool($o) {
		return self::toBoolean ( $o );
	}
	public static function toObject($o, &$ref) {
		if ($ref == null) {
			$ref = new stdClass ();
		}
		if ($o == null) {
			return $ref;
		} else {
			if (is_array ( $o ) || is_object ( $o )) {
				$ref = Utils::bindToObject ( $ref, $o, true );
			}
		}
		return $ref;
	}
	public static function toXML($o, $settings = null) {
		$root = 'root';
		$child = null;
		if (is_array ( $settings )) {
			$root = isset ( $settings ['root'] ) ? $settings ['root'] : $root;
			$child = isset ( $settings ['child'] ) ? $settings ['child'] : $child;
		}
		$xml = new SimpleXMLElement ( "<?xml version=\"1.0\"?><{$root}></{$root}>" );
		
		self::addXMLNode ( $xml, $o, $child );
		
		return $xml->asXML ();
	}
	
	private static function addXMLNode(&$xml, $o, $child) {
		foreach ( $o as $k => $v ) {
			$k = is_numeric ( $k ) ? ($child ? $child : 'el' . $k) : $k;
			if (is_array ( $v )) {
				if (substr ( $k, 0, 1 ) == '@') {
					$xml->addAttribute ( substr ( $k, 1 ), $v );
				} else {
					//$ch = $xml->addChild ( $k );
					self::addXMLNode ( $xml, $v, $k );
				}
			} else {
				if (substr ( $k, 0, 1 ) === '@') {
					$xml->addAttribute ( substr ( $k, 1 ), $v );
				} else {
					$xml->addChild ( $k, $v );
				}
			}
		}
	}
	public static function toString($o) {
		if (is_string ( $o )) {
			return $o;
		}
		if (($o instanceof Collection) || is_array ( $o )) {
			$r = "";
			foreach ( $o as $k => $v ) {
				$r .= self::toString ( $v, true );
			}
			return $r;
		}
		return $o;
	}
}
?>
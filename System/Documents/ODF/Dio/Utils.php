<?php
namespace System\Documents\ODF\Dio;
abstract class Utils {
	// kind of call_user_func_array, but for new
	function dio_new_user_class_array($class, $args) {
		$obj = null;
		$code = '$obj = new ' . $class . ' (' . implode ( ', ', self::dio_args_string ( 'args', $args ) ) . ');';
		eval ( $code );
		return $obj;
	}
	/** 
	 * Returne un tableau contenant les chaÃ®nes de chaque arguments Ã  passer
	 * Ã 
	 * eval() pour faire un appel de fonction avec les valeurs de $args.
	 */
	function dio_args_string($name, $args) {
		$cargs = array ();
		foreach ( $args as $id => $arg ) {
			switch (gettype ( $arg )) {
				case 'array' :
				case 'object' :
				case 'resource' :
					$cargs [] = "\$" . $name . "[" . $id . "]";
					break;
				default :
					$cargs [] = var_export ( $arg, true );
					break;
			}
		}
		return $cargs;
	}
	/**
	 * Generate an id from a string by replacing special chars with ascii
	 * correspondant, and replacing all non alnum chars by hyphen.
	 */
	function dio_strtoid($string) {
		static $table = array (
				// minuscule
				'Ã¡' => 'a', 
				'Ã ' => 'a', 
				'Ã¢' => 'a', 
				'Ã¤' => 'a', 
				'Ã¥' => 'a', 
				'Ã©' => 'e', 
				'Ã¨' => 'e', 
				'Ãª' => 'e', 
				'Ã«' => 'e', 
				'Ã¬' => 'i', 
				'Ã­' => 'i', 
				'Ã®' => 'i', 
				'Ã¯' => 'i', 
				'Ã³' => 'o',  
				'Ã´' => 'o', 
				'Ã¶' => 'o', 
				'Ã¸' => 'o', 
				'Ã²' => 'o', 
				'Ãº' => 'u', 
				'Ã¹' => 'u', 
				'Ã»' => 'u', 
				'Ã¼' => 'u', 
				'Ã§' => 'c', 
				'Å“' => 'oe', 
				'Ã¦' => 'ae', 
				// majuscules
				'Ã�' => 'A', 
				'Ã€' => 'A', 
				'Ã‚' => 'A', 
				'Ã„' => 'A', 
				'Ã…' => 'A', 
				'Ã‰' => 'E', 
				'Ãˆ' => 'E', 
				'ÃŠ' => 'E', 
				'Ã‹' => 'E', 
				'ÃŒ' => 'I', 
				'Ã�' => 'I', 
				'ÃŽ' => 'I', 
				'Ã�' => 'I', 
				'Ã“' => 'O', 
				'Ã”' => 'O', 
				'Ã–' => 'O', 
				'Ã˜' => 'O', 
				'Ã’' => 'O', 
				'Ãš' => 'U', 
				'Ã™' => 'U', 
				'Ã›' => 'U', 
				'Ãœ' => 'U', 
				'Ã‡' => 'C', 
				'Å’' => 'OE', 
				'Ã†' => 'AE', 
				'Â«' => '"', 
				'Â»' => '"', 
				"â€˜" => "'", 
				"â€™" => "'", 
				'â€œ' => '"', 
				'â€�' => '"', 
				'â€”' => '-', 
				'â€“' => '-', 
				'Â ' => ' ', 
				"\t" => ' ' 
		);
		$string = str_replace ( array_keys ( $table ), array_values ( $table ), $string );
		$string = preg_replace ( '/[[:punct:][:space:]]+/', '_', $string );
		$string = trim ( $string, '_' );
		return $string;
	}
}

<?php
namespace System\JSON;

//TODO using another library coz to slow....
use System\Web\JS\JSFunc;

class Service {

	public $quoteKey= true;
	public $ignoreStr= null;
	public $quoteSingle= false;
	public $ignoreNull= false;
	public $classMap= null;
	/**
	 * constructs a new JSON instance
	 *
	 * @param    int     $use    object behavior: when encoding or decoding,
	 *                           be loose or strict about object/array usage
	 *
	 *                           possible values:
	 *                              self::JSON_STRICT_TYPE - strict typing, default
	 *                                                 "{...}" syntax creates objects in decode.
	 *                               self::JSON_LOOSE_TYPE - loose typing
	 *                                                 "{...}" syntax creates associative arrays in decode.
	 */
	public function __construct($use= JSON :: JSON_STRICT_TYPE) {
		$this->use= $use;
	}
	/**
	 * encodes an arbitrary variable into JSON format
	 *
	 * @param    mixed   $var    any number, boolean, string, array, or object to be encoded.
	 *                           see argument 1 to JSON() above for array-parsing behavior.
	 *                           if var is a strng, note that encode() always expects it
	 *                           to be in ASCII or UTF-8 format!
	 *
	 * @return   string  JSON string representation of input var
	 * @access   public
	 */
	public function encode($var, $quote= true) {
		
		switch (gettype($var)) {
			case 'boolean' :				
				return $var ? 'true' : 'false';
			case 'NULL' :
				return $this->ignoreNull ? '' : 'null';
			case 'integer' :
				return (int) $var;
			case 'double' :
			case 'float' :
				return (float) $var;
			case 'string' :
				return json_encode($var);
			case 'array' :
				$ret = array();
				$hasc = false;
				
				foreach($var as $k=>$v) {
					if ( $v ===null) continue;
					if (is_numeric($k)) {
						$ret[] = $this->encode($v);
					}else{
						$hasc=true;
						if ($this->ignoreStr && in_array( $k,$this->ignoreStr)){
							
							$ret[$k] = is_string($v) ? $v : $this->encode($v) ;
						}else{
							
							$ret[$k] = $this->encode($v);
						}
						
					}
				}				
				if ($hasc) {
					$r = array();
					
					foreach($ret as $k=>$v) {
						if ($k) {
							$r []= '"'.$k.'":'.$v;
						}
						
					}
					//$r= (strlen($r) > 2 ? substr($r,0,strlen($r)-1) : $r).'}';
					
					$r = '{'.implode(','.(CGAF_DEBUG ? PHP_EOL :null),$r).'}';
					return $r;
				}
				return '[' . implode(',',$ret) . ']';;
			case 'object' :
				if ($var instanceof JSFunc) {
					return $var->Render(true);
				}elseif ($var instanceof \IJSONable){
					$vars = $var->toJSON();
					if (!is_string($vars)) {
						if (is_object($vars) && $vars instanceof JSFunc) {
							return  $vars->Render(true);
						}

						$vars = $this->encode($vars,$quote);
					}

					return $vars;

				}elseif ($var instanceof \IRenderable) {
					$handle= false;
					$retval= $var->Render(true, $handle);
					if (is_array($retval) || is_object($retval)) {
						return $this->encode($retval,$quote);
					}
					if ($handle) {
						return $retval;
					}
					else {
						return '{' . $retval . '}';
					}
				}else {
					return json_encode($var);
				}
			default :
				return '';
		}
	}
	/**
	 * encodes an arbitrary variable into JSON format, alias for encode()
	 * @see JSON::encode()
	 *
	 * @param    mixed   $var    any number, boolean, string, array, or object to be encoded.
	 *                           see argument 1 to JSON() above for array-parsing behavior.
	 *                           if var is a strng, note that encode() always expects it
	 *                           to be in ASCII or UTF-8 format!
	 *
	 * @return   string  JSON string representation of input var
	 * @access   public
	 */
	public function enc($var) {
		return $this->encode($var);
	}
	/** function name_value
	 * array-walking function for use in generating JSON-formatted name-value pairs
	 *
	 * @param    string  $name   name of key to use
	 * @param    mixed   $value  reference to an array element to be encoded
	 *
	 * @return   string  JSON-formatted name-value pair, like '"name":value'
	 * @access   private
	 */
	protected function name_value($name, $value= null) {
		$ignore= true;
		if (is_array($this->ignoreStr)) {
			$ignore= !in_array($name, $this->ignoreStr);
		}
		//event
		if (substr($name, 0, 2) === "on") {
			$ignore= false;
		}
		if (isset ($this->classMap[$name])) {
			$childs= $this->getClass($name, $value);
			$retval= join($childs, ',');
			$retval= $this->encode(strval($name), $this->quoteKey) . ':[' . $this->encode($retval, false) . ']';
		}
		else {
			if ($value !== null) {
				//pp($retval);
				$retval= $this->encode(strval($name), $this->quoteKey) . ':' . $this->encode($value, $ignore);
			}
			else {
				if (is_array($name)) {
					$retval= $this->encode($name, $ignore);
				}
				else {
					if ($this->ignoreNull && $value === null) {
						$retval= '';
					}
					else {
						$retval= $this->encode(strval($name), $this->quoteKey) . ':' . $this->encode($value, $ignore);
					}
				}
			}
		}
		return $retval;
	}
	/**
	 * reduce a string by removing leading and trailing comments and whitespace
	 *
	 * @param    $str    string      string value to strip of comments and whitespace
	 *
	 * @return   string  string value stripped of comments and whitespace
	 * @access   private
	 */
	protected function reduce_string($str) {
		$str= preg_replace(array (
		// eliminate single line comments in '// ...' form
	'#^\s*//(.+)$#m',
		// eliminate multi-line comments in '/* ... */' form, at start of string
	'#^\s*/\*(.+)\*/#Us',
		// eliminate multi-line comments in '/* ... */' form, at end of string
	'#/\*(.+)\*/\s*$#Us'
		), '', $str);
		// eliminate extraneous space
		return trim($str);
	}
	/**
	 * decodes a JSON string into appropriate variable
	 *
	 * @param    string  $str    JSON-formatted string
	 *
	 * @return   mixed   number, boolean, string, array, or object
	 *                   corresponding to given JSON input string.
	 *                   See argument 1 to JSON() above for object-output behavior.
	 *                   Note that decode() always returns strings
	 *                   in ASCII or UTF-8 format!
	 * @access   public
	 */
	public function decode($str) {
		$str= $this->reduce_string($str);
		$m = $parts = null;
		switch (strtolower($str)) {
			case 'true' :
				return true;
			case 'false' :
				return false;
			case 'null' :
				return null;
			default :
				if (is_numeric($str)) {
				// Lookie-loo, it's a number
				// This would work on its own, but I'm trying to be
				// good about returning integers where appropriate:
				// return (float)$str;
				// Return float or int, as appropriate
				return ((float) $str == (integer) $str) ? (integer) $str : (float) $str;
			}
			elseif (preg_match('/^("|\').+(\1)$/s', $str, $m) && $m[1] == $m[2]) {
				// STRINGS RETURNED IN UTF-8 FORMAT
				$delim= substr($str, 0, 1);
				$chrs= substr($str, 1, -1);
				$utf8= '';
				$strlen_chrs= strlen($chrs);
				for ($c= 0; $c < $strlen_chrs; ++ $c) {
					$substr_chrs_c_2= substr($chrs, $c, 2);
					$ord_chrs_c= ord($chrs {
						$c });
						switch (true) {
							case $substr_chrs_c_2 == '\b' :
								$utf8 .= chr(0x08);
								++ $c;
								break;
							case $substr_chrs_c_2 == '\t' :
								$utf8 .= chr(0x09);
								++ $c;
								break;
							case $substr_chrs_c_2 == '\n' :
								$utf8 .= chr(0x0A);
								++ $c;
								break;
							case $substr_chrs_c_2 == '\f' :
								$utf8 .= chr(0x0C);
								++ $c;
								break;
							case $substr_chrs_c_2 == '\r' :
								$utf8 .= chr(0x0D);
								++ $c;
								break;
							case $substr_chrs_c_2 == '\\"' :
							case $substr_chrs_c_2 == '\\\'' :
							case $substr_chrs_c_2 == '\\\\' :
							case $substr_chrs_c_2 == '\\/' :
								if (($delim == '"' && $substr_chrs_c_2 != '\\\'') || ($delim == "'" && $substr_chrs_c_2 != '\\"')) {
									$utf8 .= $chrs {
										++ $c };
								}
								break;
							case preg_match('/\\\u[0-9A-F]{4}/i', substr($chrs, $c, 6)) :
								// single, escaped unicode character
								$utf16= chr(hexdec(substr($chrs, ($c +2), 2))) . chr(hexdec(substr($chrs, ($c +4), 2)));
								$utf8 .= $this->utf16be_to_utf8($utf16);
								$c += 5;
								break;
							case ($ord_chrs_c >= 0x20) && ($ord_chrs_c <= 0x7F) :
								$utf8 .= $chrs {
									$c };
									break;
							case ($ord_chrs_c & 0xE0) == 0xC0 :
								// characters U-00000080 - U-000007FF, mask 110XXXXX
								//see http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
								$utf8 .= substr($chrs, $c, 2);
								++ $c;
								break;
							case ($ord_chrs_c & 0xF0) == 0xE0 :
								// characters U-00000800 - U-0000FFFF, mask 1110XXXX
								// see http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
								$utf8 .= substr($chrs, $c, 3);
								$c += 2;
								break;
							case ($ord_chrs_c & 0xF8) == 0xF0 :
								// characters U-00010000 - U-001FFFFF, mask 11110XXX
								// see http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
								$utf8 .= substr($chrs, $c, 4);
								$c += 3;
								break;
							case ($ord_chrs_c & 0xFC) == 0xF8 :
								// characters U-00200000 - U-03FFFFFF, mask 111110XX
								// see http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
								$utf8 .= substr($chrs, $c, 5);
								$c += 4;
								break;
							case ($ord_chrs_c & 0xFE) == 0xFC :
								// characters U-04000000 - U-7FFFFFFF, mask 1111110X
								// see http://www.cl.cam.ac.uk/~mgk25/unicode.html#utf-8
								$utf8 .= substr($chrs, $c, 6);
								$c += 5;
								break;
						}
				}
				return $utf8;
			}
			elseif (preg_match('/^\[.*\]$/s', $str) || preg_match('/^\{.*\}$/s', $str)) {
				// array, or object notation
				if ($str {
					0 }
					== '[') {
						$stk= array (
						JSON :: JSON_IN_ARR
						);
						$arr= array ();
					}
					else {
						if ($this->use == JSON :: JSON_LOOSE_TYPE) {
							$stk= array (
							JSON:: JSON_IN_OBJ
							);
							$obj= array ();
						}
						else {
							$stk= array (
							JSON :: JSON_IN_OBJ
							);
							$obj= new \stdClass();
						}
					}
					array_push($stk, array (
			'what' => JSON :: JSON_SLICE,
			'where' => 0,
			'delim' => false
					));
					$chrs= substr($str, 1, -1);
					$chrs= $this->reduce_string($chrs);
					if ($chrs == '') {
						if (reset($stk) == JSON :: JSON_IN_ARR) {
							return $arr;
						}
						else {
							return $obj;
						}
					}
					//print("\nparsing {$chrs}\n");
					$strlen_chrs= strlen($chrs);
					for ($c= 0; $c <= $strlen_chrs; ++ $c) {
						$top= end($stk);
						$substr_chrs_c_2= substr($chrs, $c, 2);
						if (($c == $strlen_chrs) || (($chrs {
							$c }
							== ',') && ($top['what'] == JSON :: JSON_SLICE))) {
								// found a comma that is not inside a string, array, etc.,
								// OR we've reached the end of the character list
								$slice= substr($chrs, $top['where'], ($c - $top['where']));
								array_push($stk, array (
		'what' => JSON:: JSON_SLICE,
		'where' => ($c +1),
		'delim' => false
								));
								//print("Found split at {$c}: ".substr($chrs, $top['where'], (1 + $c - $top['where']))."\n");
								if (reset($stk) == JSON :: JSON_IN_ARR) {
									// we are in an array, so just push an element onto the stack
									array_push($arr, $this->decode($slice));
								}
								elseif (reset($stk) == JSON :: JSON_IN_OBJ) {
									// we are in an object, so figure
									// out the property name and set an
									// element in an associative array,
									// for now
									if (preg_match('/^\s*(["\'].*[^\\\]["\'])\s*:\s*(\S.*),?$/Uis', $slice, $parts)) {
										// "name":value pair
										$key= $this->decode($parts[1]);
										$val= $this->decode($parts[2]);
										if ($this->use == JSON :: JSON_LOOSE_TYPE) {
											$obj[$key]= $val;
										}
										else {
											$obj-> $key= $val;
										}
									}
									elseif (preg_match('/^\s*(\w+)\s*:\s*(\S.*),?$/Uis', $slice, $parts)) {
										// name:value pair, where name is unquoted
										$key= $parts[1];
										$val= $this->decode($parts[2]);
										if ($this->use == JSON :: JSON_LOOSE_TYPE) {
											$obj[$key]= $val;
										}
										else {
											$obj-> $key= $val;
										}
									}
								}
							}
							elseif ((($chrs {
								$c }
								== '"') || ($chrs {
									$c }
									== "'")) && ($top['what'] != JSON :: JSON_IN_STR)) {
										// found a quote, and we are not inside a string
										array_push($stk, array (
				'what' => JSON:: JSON_IN_STR,
								'where' => $c,
								'delim' => $chrs {
										$c }
										));
										//print("Found start of string at {$c}\n");
									}
									elseif (($chrs {
										$c }
										== $top['delim']) && ($top['what'] == JSON:: JSON_IN_STR) && (($chrs {
											$c -1 }
											!= "\\") || ($chrs {
												$c -1 }
												== "\\" && $chrs {
													$c -2 }
													== "\\"))) {
														// found a quote, we're in a string, and it's not escaped
														array_pop($stk);
														//print("Found end of string at {$c}: ".substr($chrs, $top['where'], (1 + 1 + $c - $top['where']))."\n");
													}
													elseif (($chrs {
														$c }
														== '[') && in_array($top['what'], array (
														JSON :: JSON_SLICE,
														JSON :: JSON_IN_ARR,
														JSON :: JSON_IN_OBJ
														))) {
															// found a left-bracket, and we are in an array, object, or slice
															array_push($stk, array (
								'what' => JSON :: JSON_IN_ARR,
								'where' => $c,
								'delim' => false
															));
															//print("Found start of array at {$c}\n");
														}
														elseif (($chrs {
															$c }
															== ']') && ($top['what'] == JSON:: JSON_IN_ARR)) {
																// found a right-bracket, and we're in an array
																array_pop($stk);
																//print("Found end of array at {$c}: ".substr($chrs, $top['where'], (1 + $c - $top['where']))."\n");
															}
															elseif (($chrs {
																$c }
																== '{') && in_array($top['what'], array (
																JSON :: JSON_SLICE,
																JSON :: JSON_IN_ARR,
																JSON :: JSON_IN_OBJ
																))) {
																	// found a left-brace, and we are in an array, object, or slice
																	array_push($stk, array (
								'what' => JSON:: JSON_IN_OBJ,
								'where' => $c,
								'delim' => false
																	));
																	//print("Found start of object at {$c}\n");
																}
																elseif (($chrs {
																	$c }
																	== '}') && ($top['what'] == JSON :: JSON_IN_OBJ)) {
																		// found a right-brace, and we're in an object
																		array_pop($stk);
																		//print("Found end of object at {$c}: ".substr($chrs, $top['where'], (1 + $c - $top['where']))."\n");
																	}
																	elseif (($substr_chrs_c_2 == '/*') && in_array($top['what'], array (
																	JSON:: JSON_SLICE,
																	JSON:: JSON_IN_ARR,
																	JSON:: JSON_IN_OBJ
																	))) {
																		// found a comment start, and we are in an array, object, or slice
																		array_push($stk, array (
									'what' => JSON:: JSON_IN_CMT,
									'where' => $c,
									'delim' => false
																		));
																		$c++;
																		//print("Found start of comment at {$c}\n");
																	}
																	elseif (($substr_chrs_c_2 == '*/') && ($top['what'] == JSON:: JSON_IN_CMT)) {
																		// found a comment end, and we're in one now
																		array_pop($stk);
																		$c++;
																		for ($i= $top['where']; $i <= $c; ++ $i)
																		$chrs= substr_replace($chrs, ' ', $i, 1);
																		//print("Found end of comment at {$c}: ".substr($chrs, $top['where'], (1 + $c - $top['where']))."\n");
																	}
					}
					if (reset($stk) == JSON:: JSON_IN_ARR) {
						return $arr;
					}
					elseif (reset($stk) == JSON:: JSON_IN_OBJ) {
						return $obj;
					}
			}
		}
		return null;
	}
	/**
	 * decodes a JSON string into appropriate variable; alias for decode()
	 * @see JSON::decode()
	 *
	 * @param    string  $str    JSON-formatted string
	 *
	 * @return   mixed   number, boolean, string, array, or object
	 *                   corresponding to given JSON input string.
	 *                   See argument 1 to JSON() above for object-output behavior.
	 *                   Note that decode() always returns strings
	 *                   in ASCII or UTF-8 format!
	 */
	public function dec($var) {
		return $this->decode($var);
	}
	/**
	 * This function returns any UTF-8 encoded text as a list of
	 * Unicode values:
	 *
	 * @author Scott Michael Reynen <scott@randomchaos.com>
	 * @link   http://www.randomchaos.com/document.php?source=php_and_unicode
	 * @see    unicode_to_utf8()
	 */
	protected function utf8_to_unicode(& $str) {
		$unicode= array ();
		$values= array ();
		$lookingFor= 1;
		for ($i= 0; $i < strlen($str); $i++) {
			$thisValue= ord($str[$i]);
			if ($thisValue < 128)
			$unicode[]= $thisValue;
			else {
				if (count($values) == 0)
				$lookingFor= ($thisValue < 224) ? 2 : 3;
				$values[]= $thisValue;
				if (count($values) == $lookingFor) {
					$number= ($lookingFor == 3) ? (($values[0] % 16) * 4096) + (($values[1] % 64) * 64) + ($values[2] % 64) : (($values[0] % 32) * 64) + ($values[1] % 64);
					$unicode[]= $number;
					$values= array ();
					$lookingFor= 1;
				}
			}
		}
		return $unicode;
	}
	/**
	 * This function converts a Unicode array back to its UTF-8 representation
	 *
	 * @author Scott Michael Reynen <scott@randomchaos.com>
	 * @link   http://www.randomchaos.com/document.php?source=php_and_unicode
	 * @see    utf8_to_unicode()
	 */
	protected function unicode_to_utf8(& $str) {
		$utf8= '';
		foreach ($str as $unicode) {
			if ($unicode < 128) {
				$utf8 .= chr($unicode);
			}
			elseif ($unicode < 2048) {
				$utf8 .= chr(192 + (($unicode - ($unicode % 64)) / 64));
				$utf8 .= chr(128 + ($unicode % 64));
			}
			else {
				$utf8 .= chr(224 + (($unicode - ($unicode % 4096)) / 4096));
				$utf8 .= chr(128 + ((($unicode % 4096) - ($unicode % 64)) / 64));
				$utf8 .= chr(128 + ($unicode % 64));
			}
		}
		return $utf8;
	}
	/**
	 * UTF-8 to UTF-16BE conversion.
	 *
	 * Maybe really UCS-2 without mb_string due to utf8_to_unicode limits
	 */
	protected function utf8_to_utf16be(& $str, $bom= false) {
		$out= $bom ? "\xFE\xFF" : '';
		if (function_exists('mb_convert_encoding'))
		return $out . mb_convert_encoding($str, 'UTF-16BE', 'UTF-8');
		$uni= $this->utf8_to_unicode($str);
		foreach ($uni as $cp)
		$out .= pack('n', $cp);
		return $out;
	}
	/**
	 * UTF-8 to UTF-16BE conversion.
	 *
	 * Maybe really UCS-2 without mb_string due to utf8_to_unicode limits
	 */
	protected function utf16be_to_utf8(& $str) {
		$uni= unpack('n*', $str);
		return $this->unicode_to_utf8($uni);
	}
	protected function getClass($keys, $var) {
		$class= $this->classMap[$keys];
		$retval= array ();
		foreach ($var as $k => $v) {
			if (is_array($v)) {
				$retval[$k]= 'new ' . $class . '(' . $this->encode($v) . ')';
			}
		}
		return $retval;
	}
}
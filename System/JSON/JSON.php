<?php
namespace System\JSON;
/**
 * Converts to and from JSON format.
 *
 * JSON (JavaScript Object Notation) is a lightweight data-interchange
 * format. It is easy for humans to read and write. It is easy for machines
 * to parse and generate. It is based on a subset of the JavaScript
 * Programming Language, Standard ECMA-262 3rd Edition - December 1999.
 * This feature can also be found in  Python. JSON is a text format that is
 * completely language independent but uses conventions that are familiar
 * to programmers of the C-family of languages, including C, C++, C#, Java,
 * JavaScript, Perl, TCL, and many others. These properties make JSON an
 * ideal data-interchange language.
 *
 * This package provides a simple encoder and decoder for JSON notation. It
 * is intended for use with client-side Javascript applications that make
 * use of HTTPRequest to perform server communication functions - data can
 * be encoded into JSON notation for use in a client-side javascript, or
 * decoded from incoming Javascript requests. JSON format is native to
 * Javascript, and can be directly eval()'ed with no further parsing
 * overhead
 *
 * All strings should be in ASCII or UTF-8 format!
 *
 * PHP versions 4 and 5
 *
 * LICENSE: Redistribution and use in source and binary forms, with or
 * without modification, are permitted provided that the following
 * conditions are met: Redistributions of source code must retain the
 * above copyright notice, this list of conditions and the following
 * disclaimer. Redistributions in binary form must reproduce the above
 * copyright notice, this list of conditions and the following disclaimer
 * in the documentation and/or other materials provided with the
 * distribution.
 *
 * THIS SOFTWARE IS PROVIDED ``AS IS'' AND ANY EXPRESS OR IMPLIED
 * WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF
 * MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN
 * NO EVENT SHALL CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
 * BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS
 * OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR
 * TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE
 * USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH
 * DAMAGE.
 *
 * @package     System.Web.Javascripts
 * @author      Michal Migurski <mike-json@teczno.com>
 * @author      Matt Knapp <mdknapp[at]gmail[dot]com>
 * @author      Brett Stimmerman <brettstimmerman[at]gmail[dot]com>
 * @copyright   2005 Michal Migurski
 * @license     http://www.opensource.org/licenses/bsd-license.php
 * @link        http://pear.php.net/pepr/pepr-proposal-show.php?id=198
 */
/**
 * Converts to and from JSON format.
 *
 * @package    System.Web.Javascripts
 * @author     Michal Migurski <mike-json@teczno.com>
 * @author     Matt Knapp <mdknapp[at]gmail[dot]com>
 * @author     Brett Stimmerman <brettstimmerman[at]gmail[dot]com>
 * @copyright  2005 Michal Migurski
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 */



abstract class JSON {
	/**
	 * Marker constant for JSON::decode(), used to flag stack state
	 */
	const JSON_SLICE= 1;
	/**
	 * Marker constant for JSON::decode(), used to flag stack state
	 */
	const JSON_IN_STR= 2;
	/**
	 * Marker constant for JSON::decode(), used to flag stack state
	 */
	const JSON_IN_ARR= 4;
	/**
	 * Marker constant for JSON::decode(), used to flag stack state
	 */
	const JSON_IN_OBJ= 8;
	/**
	 * Marker constant for JSON::decode(), used to flag stack state
	 */
	const JSON_IN_CMT= 16;
	/**
	 * Behavior switch for JSON::decode()
	 */
	const JSON_LOOSE_TYPE= 10;
	/**
	 * Behavior switch for JSON::decode()
	 */
	const JSON_STRICT_TYPE= 11;

	public static function encode($var, $single= false, $ignorestr= null) {
		$json= new Service(self :: JSON_STRICT_TYPE);
		$json->quoteKey= true;
		$json->ignoreStr= $ignorestr;
		$json->quoteSingle= $single;
		return $json->encode($var);
	}
	public static function decode($txt) {
		$json= new Service(self :: JSON_STRICT_TYPE);
		return $json->decode($txt);
	}
	public static function encodeConfig($var, $ignorestr= null, $classMap= null) {
		$json= new Service();
		$json->classMap= $classMap;
		$json->quoteKey= false;
		$json->ignoreStr= $ignorestr;
		$json->quoteSingle= true;
		if (is_array($ignorestr)) {
			$quote= true;
		}
		else {
			$quote= $ignorestr;
		}
		$retval= $json->encode($var, $quote);
		//$retval = substr($retval,1,strlen($retval)-2);
		return $retval;
	}
	public static function encodeSimple($data, $quote= "'") {
		$retval= array ();
		foreach ($data as $k => $v) {
			if (is_array($v) || is_object($v)) {
				$ret= "";
				foreach ($v as $val) {
					$ret[]= "$quote{$val}$quote";
				}
				$retval[]= "[" . join($ret, ",") . "]";
			}
			else {
				$retval[]= "[$quote{$k}$quote,$quote{$v}$quote]";
			}
		}
		return "[" . join($retval, ",") . "]";
	}
	public static function getJSONData($data,$rescount=null){
		$json= new JSONSimpleData();
		$json->results= $rescount ? $rescount : count($data);
		if (!is_array($data)) {
			$data=array();
		}
		$json->rows= $data;
		return $json;
	}
	public static function toJSONData($data, $key= "id", $rescount= null,$includeField =false) {
		$json = self::getJSONData($data,$key,$rescount,$includeField);
		return $json->render();
	}
	public static function json_pp($json) {
		$tab = '  ';
		$new_json = '';
		$indent_level = 0;
		$in_string = false;

		$json_obj = json_decode($json);

		if(!$json_obj) {
			return $new_json;
		}

		$json = json_encode($json_obj);
		$len = strlen($json);

		for($c = 0; $c < $len; $c++) {
			$char = $json[$c];
			switch($char) {
				case '{':
				case '[':
					if(!$in_string) {
						$new_json .= $char . "\n" . str_repeat($tab, $indent_level + 1);
						$indent_level++;
					} else {
						$new_json .= $char;
					}
					break;
				case '}':
				case ']':
					if(!$in_string) {
						$indent_level--;
						$new_json .= "\n" . str_repeat($tab, $indent_level) . $char;
					} else {
						$new_json .= $char;
					}
					break;
				case ',':
					if(!$in_string) {
						$new_json .= ",\n" . str_repeat($tab, $indent_level);
					} else {
						$new_json .= $char;
					}
					break;
				case ':':
					if(!$in_string) {
						$new_json .= ': ';
					} else {
						$new_json .= $char;
					}
					break;
				case '"':
					$in_string = !$in_string;
				default:
					$new_json .= $char;
				break;
			}
		}
		return $new_json;
	}
}


?>
<?php
namespace System\Web;
use \Utils;
class Response extends \System\AbstractResponse {	
	function __construct() {
		parent::__construct ( true );
		
	}
	
	function Init() {
		parent::Init ();
	}
	function flush() {
		header ( "filetype:text/html" );
		return parent::flush();
	}
	function Write($s, $attr = null) {
		if ($s === null) {
			return;
		}
		if ($attr !== null) {
			$attr = new \TAttribute ( $attr );
			$attr->addIgnore ( "__tag" );
			$tag = $attr->get ( "__tag" );
			$s = isset ( $tag ) ? "<$tag " . $attr->Render ( true ) . ">$s</$tag>\n" : $s;
		}
		return parent::write ( $s );
	}
	function forceContentExpires() {
		header ( "Cache-Control: no-cache, must-revalidate" ); // HTTP/1.1
		header ( "Expires: Mon, 26 Jul 1997 05:00:00 GMT" ); // Date in the past
	}
	function Redirect($url = null) {	
		$url = $url ? $url : BASE_URL;
		$this->forceContentExpires ();
		$this->clearBuffer ();
		if (\Request::isJSONRequest ()) {
			$r = new JSONResult ( true, '', $url );
			$this->write ( $r->render ( true ) );
			return;
		}
		header ( "Location: $url" );
		exit ();
	}
}
?>

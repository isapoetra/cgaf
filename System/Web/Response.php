<?php
if (! defined ( "CGAF" ))
	die ( "Restricted Access" );

class WEbResponse extends TResponse {
	
	function __construct() {
		parent::__construct ( true );
		header ( "filetype:text/html" );
	}
	
	function Init() {
		parent::Init ();
	}
	
	function Write($s, $attr = null) {
		if ($s === null) {
			return;
		}
		if ($attr !== null) {
			$attr = new TAttribute ( $attr );
			$attr->addIgnore ( "__tag" );
			$tag = $attr->get ( "__tag" );
			$s = isset ( $tag ) ? "<$tag " . $attr->Render ( true ) . ">$s</$tag>\n" : $s;
		}
		parent::write ( $s );
	}
	function forceContentExpires() {
		header ( "Cache-Control: no-cache, must-revalidate" ); // HTTP/1.1
		header ( "Expires: Mon, 26 Jul 1997 05:00:00 GMT" ); // Date in the past
	}
	function Redirect($url = null) {	
		$this->forceContentExpires ();
		$this->clearBuffer ();
		if (Request::isJSONRequest ()) {
			$r = new JSONResult ( true, '', $url );
			$this->write ( $r->render ( true ) );
			return;
		}
		header ( "Location: $url" );
		exit ();
	}
}
?>

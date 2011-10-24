<?php
using('System.Web.feed.base');
/**
 * @example
 * <?xml version="1.0" encoding="utf-8"?>

<feed xmlns="http://www.w3.org/2005/Atom">

	<title>Example Feed</title>
	<subtitle>A subtitle.</subtitle>
	<link href="http://example.org/feed/" rel="self" />
	<link href="http://example.org/" />
	<id>urn:uuid:60a76c80-d399-11d9-b91C-0003939e0af6</id>
	<updated>2003-12-13T18:30:02Z</updated>
	<author>
		<name>John Doe</name>
		<email>johndoe@example.com</email>
	</author>

	<entry>
		<title>Atom-Powered Robots Run Amok</title>
		<link href="http://example.org/2003/12/13/atom03" />
		<link rel="alternate" type="text/html" href="http://example.org/2003/12/13/atom03.html"/>
		<link rel="edit" href="http://example.org/2003/12/13/atom03/edit"/>
		<id>urn:uuid:1225c695-cfb8-4ebb-aaaa-80da344efa6a</id>
		<updated>2003-12-13T18:30:02Z</updated>
		<summary>Some text.</summary>
	</entry>

</feed>

 * @author e1
 *
 */
class TAtomFeed extends TBaseFeed {
	function __construct() {
		$this->setValidHeader(array(
			'title','subtitle','link','id','updated','author'
		));
	}
	function Render($return=false) {
		$retval = '<?xml version="1.0" encoding="utf-8"?>';
 		$retval .= '<feed xmlns="http://www.w3.org/2005/Atom">';

 		$retval .= '</feed>';

		if (!$return) {
			echo $retval;
		}
	}
}
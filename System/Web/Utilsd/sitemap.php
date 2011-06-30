<?php
class SiteMapRenderer {
	private $_namespaces;
	function addNameSpace($name,$namespace) {
		$this->_namespaces[] = array("name"=>$name,"namespace"=>$namespace);
	}
}
class XMLElement {
	private $_childs = array();
	private $_tag = null;
	function __construct($tag,$value=null) {
		$this->_tag =$tag;
		if ($value && $value instanceof XMLElement) {
			$this->addChild($value);
		}
	}
	function addChild($child) {
		$this->_childs[]  = $child;
	}
	function Render() {
	}
}
class SiteMapItem extends XMLElement {

}

class SiteMapItemGeo extends SiteMapItem {

	function __construct($location,$format) {
		$element = new XMLElement('url');
		$element->addChild(new XMLElement("loc",$location));
		$element->addChild(new XMLElement("geo:geo",new XMLElement("geo:format",$format)));
		$this->addChild($element );
	}
	function render(SiteMapRenderer $parent) {
		$parent->addNameSpace('geo', "http://www.google.com/geo/schemas/sitemap/1.0");
	}
}


class SiteMap {
	private function __construct() {
	}
	public static function getInstance() {
		static $instance;
	}
	public function generate($obj) {
		$xml = '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:codesearch="http://www.google.com/codesearch/schemas/sitemap/1.0">';

	}
}
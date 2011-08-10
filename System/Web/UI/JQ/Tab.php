<?php
namespace System\Web\UI\JQ;
use \String;
use System\JSON\JSON;
class Tab extends Control {
	private $_tabs;
	public $DisplayHeaderContent = true;
	function __construct($id, $template = null, $tabs = array()) {
		parent::__construct ( $id, $template );
		$this->_tabs = $tabs;
	}
	function addTab($tab) {
		$this->_tabs [] = $tab;
	}
	function Render($return = false) {
		$id = $this->getId ();

		$retval = "<div id=\"$id-tabs\">";
		$retval .= "<ul>";
		$i = 1;
		foreach ( $this->_tabs as $step ) {
			$link = "#$id-tab-$i";
			if (String::BeginWith ( $step ["content"], "http" )) {
				$link = $step ["content"];
				$link = URLHelper::addParam ( $link, array(
					'_ajax'=>1) );
			}
			$retval .= "<li><a href=\"$link\">" . $step ["title"] . "</a></li>";
			$i ++;
		}
		$retval .= "</ul>";

		$i = 1;
		foreach ( $this->_tabs as $step ) {
			if (String::BeginWith ( $step ["content"], "http" )) {
				continue;
			}

			$retval .= "<div id=\"$id-tab-$i\">";
			if ($this->DisplayHeaderContent) {
				$retval .= "<h2>" . $step ["title"] . "</h2>";
			}
			$retval .= "<div>" . $step ["content"] . "</div>";
			$retval .= "</div>";
			$i ++;
		}
		//ppd($retval);
		$retval .= "</div>";
		$config = JSON::encodeConfig ( $this->_configs, array(
			'show') );
		$this->getAppOwner()->addClientScript ( "$(\"#$id-tabs\").tabs($.extend($config,deftaboptions||{}))" );
		if (! $return) {
			Response::write ( $retval );
		}
		return $retval;
	}
}
?>
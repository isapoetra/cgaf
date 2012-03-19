<?php
namespace System\Web\UI\JQ;
use System\JSON\JSON;
use System\Web\JS\CGAFJS;
class CoverFlow extends Control {
	private $_theme = 'black';
	function __construct($id, $configs = array()) {
		parent::__construct($id);
		$this->setConfig($configs);
	}
	public function setTheme($value) {
		$this->_theme = $value;
	}
	public function Render($return = false) {
		if (\Request::isDataRequest()) {
			return $this->renderData();
		}
		$id = $this->getId();
		//CGAFJS::loadPlugin('coverflow/coverflow', true);
		//CGAFJS::addJQAsset('plugins/coverflow/coverflow.css');
		//CGAFJS::addJQAsset('plugins/coverflow/coverflow-' . $this->_theme . '.css');
		$parent = $this->getConfig('renderTo', 'body');
		$this->removeConfig('renderTo');
		$config = JSON::encodeConfig($this->_configs);
		$js = <<< EOT
			var d = $('#$id');
			if (d.length ===0) {
				d=$('<div id="$id"></div>').appendTo('$parent');
			}
		d.coverflow($config);
EOT;
		$this->getAppOwner()->addClientScript($js);
		return '<div id="' . $id . '"></div>';
	}
}

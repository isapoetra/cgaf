<?php
namespace System\Web\UI\Controls;

use System\JSON\JSON;
class FlowPlayer extends WebControl {
	private $_flowplayerversion = '3.2.7';
	private $_flowSource;
	private $_flashConfig = array('log' => array('level' => 'debug'));
	private $_configs = array('debug' => CGAF_DEBUG, 'log' => array('level' => 'debug'));
	function __construct($configs = array()) {
		parent::__construct('div');
		$this->_flowSource = ASSET_URL . 'swf/flowplayer/';
		$this->addEventListener('onError', 'function(code,msg) {console.log(arguments);}');
		$this->_configs['plugins']['controls'] = array('url' => $this->_flowSource . 'flowplayer.controls-3.2.5.swf', 
				// always: where to find the Flash object
				'playlist' => true, 
				// now the custom options of the Flash object
				'backgroundColor' => '#aedaff', 'tooltips' => array(
				// this plugin object exposes a 'tooltips' object
				'buttons' => true, 'fullscreen' => 'Enter Fullscreen mode'));
		$this->_configs = array_merge_recursive($this->_configs, $configs);
	}
	function addPlaylist($playlist) {
		if (is_array($playlist)) {
			foreach ($playlist as $p) {
				$this->addPlaylist($p);
			}
			return;
		}
		if (!isset($this->_configs['playlist'])) {
			$this->_configs['playlist'] = array();
		}
		$this->_configs['playlist'][] = $playlist;
	}
	function setClip($clip) {
		$this->_configs['clip'] = $clip;
	}
	private function getEvents() {
		$retval = array();
		foreach ($this->_events as $k => $v) {
			$retval[$k] = $v[0];
		}
		return $retval;
	}
	function prepareRender() {
		parent::prepareRender();
		$appOwner = $this->getAppOwner();
		$appOwner->addClientAsset($this->_flowSource . 'flowplayer-' . $this->_flowplayerversion . '.js');
		$this->_flashConfig['src'] = $this->_flowSource . 'flowplayer-' . $this->_flowplayerversion . '.swf';
		$fconfig = JSON::encodeConfig($this->_flashConfig);
		$this->_configs = array_merge($this->_configs, $this->getEvents());
		$configs = JSON::encodeConfig($this->_configs, array_keys($this->_events));
		$appOwner->addClientScript('$f("' . $this->getId() . '",' . $fconfig . ',' . $configs . ');');
	}
}

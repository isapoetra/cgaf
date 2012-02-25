<?php
namespace System\Web\UI\JQ;
use System\Session\Session;

use System\JSON\JSON;

class Upload extends Control {
	private $_uploadEngine = 'uploadify/jquery.uploadify.v2.1.0.js';
	private $_css = 'js/uploadify/uploadify.css';
	private $_scriptdata = array ();
	function __construct($id, ITemplate $template = null) {
		parent::__construct ( $id, $template );
	}
	function setCSS($css) {
		$this->_css = $css;
		return $this;
	}
	function setMulti($value) {
		return $this->setConfig ( 'multi', $value );
	}
	function addScriptData($key, $val) {
		$this->_scriptdata [$key] = $val;
	}
	function prepareRender() {
		$this->getTemplate ()->addAsset ( 'swfobject.js' );
		$this->_scriptdata = array_merge ( $this->_scriptdata, array (
				'PHPSESSID' =>Session::getId () 
		) );
		$this->addEvent ( 'onComplete', '$.uploadComplete', false );
		$this->addEvent ( 'onError', '$.uploadError', false );
		$this->setConfig ( array (
				'uploader' => BASE_URL . '/Data/js/uploadify/uploadify.swf?PHPSESSID=' . session_id (), 
				'script' => 'scripts/uploadify.php', 
				'cancelImg' => $this->getTemplate ()->getAppOwner ()->getLiveData ( 'cancel.png' ), 
				'scriptData' => $this->_scriptdata, 
				'folder' => 'uploads', 
				'queueID' => $this->getId () . 'fileQueue', 
				'auto' => true, 
				'multi' => true 
		), null, false );
	}
	function getContainerId() {
		return $this->getId () . 'container';
	}
	function RenderScript($return = false) {
		$this->getTemplate ()->addAsset ( $this->_uploadEngine );
		$this->getTemplate ()->addAsset ( 'uploadify/uploadHandler.js' );
		if ($this->_css) {
			$this->getTemplate ()->addCSSFile ( $this->_css );
		}
		$id = $this->getId ();
		$script = "\n$(\"#$id\").uploadify(" . JSON::encodeConfig ( $this->_configs ) . ")";
		$this->getTemplate ()->addClientScript ( $script );
		$retval = '<div id="' . $this->getContainerId () . '">' . ' <div id="' . $this->getConfig ( 'queueID' ) . '">&nbsp;</div>' . '<input type="file" name="' . $id . '" id="' . $id . '" />' . '<p><a href="javascript:$(\'#' . $id . '\').uploadifyClearQueue()">Cancel All Uploads</a></p>' . '<div class="preview" style="display:none">&nbsp;</div></div>';
		return $retval;
	}
}
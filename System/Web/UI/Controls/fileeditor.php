<?php
namespace System\Web\UI\Controls;

use System\Web\UI\JQ\HTMLEditor;
use System\Configurations\Configuration;

class FileEditor extends WebControl {
	private $_configs = array();
	private $_file;
	private $_content = null;
	function __construct($file, $configs = array()) {
		parent::__construct('div');
		$this->_file = $file;
		$this->_configs = new Configuration($configs, false);
		$this->setEditorConfig(array('filebrowserImageBrowseUrl' => BASE_URL . '/asset/browse/?type=images', 'skin' => 'kama', 'uiColor' => '#9AB8F3', 'toolbarCanCollapse' => true, 'language' => $this->getAppOwner()->getLocale()->getLocale()));
	}
	function setEditorConfig($config, $value = null) {
		$this->_configs->setConfig($config, $value);
	}
	function setContent($content) {
		$this->_content = $content;
	}
	function prepareRender() {
		$appOwner = $this->getAppOwner();
		$ext = \Utils::getFileExt($this->_file, false);
		/**
		 *
		 * Enter description here ...
		 * @var \IContentEditor
		 */
		$instance = null;
		$id = $this->getId();
		$this->setId($id . '-container');
		switch ($ext) {
		case 'js':
		case 'html':
			$instance = new HTMLEditor($id);
			break;
		default:
			break;
		}
		if (!$instance) {
			throw new \Exception('unhandled file editor for extension ' . $ext . ' Please Contact Vendor');
		}
		if ($this->_file) {
			$content = file_get_contents($this->_file);
			$instance->setContent($content ? $content : '');
		} else {
			$instance->setContent($this->_content);
		}
		$instance->setConfig($this->_configs->getConfigs('System'));
		$this->addChild($instance);
		return parent::prepareRender();
	}
}

<?php
namespace System\Web\UI\Controls;
use System\Locale\Locale;
use System\Configurations\Configuration;
use System\JSON\JSON;
class FileEditor extends WebControl {
	private $_configs = array();
	private $_file;
	private $_content =null;
	function __construct($file, $configs = array()) {
		parent::__construct('textarea');
		$this->_file=$file;
		$this->_configs = new Configuration($configs, false);
		$this->setEditorConfig(array(
						'filebrowserImageBrowseUrl'=>BASE_URL.'/asset/browse/?type=images',
						'skin' => 'kama',
						'uiColor' => '#9AB8F3',
						'toolbarCanCollapse'=>true,
						'language' => $this->getAppOwner()->getLocale()->getLocale()));
	}
	function setEditorConfig($config, $value = null) {
		$this->_configs->setConfig($config, $value);
	}
	function setContent($content) {
		$this->_content = $content;
	}
	function prepareRender() {
		$appOwner = $this->getAppOwner();

		if ($this->_file && $content=file_get_contents($this->_file)) {
			$this->setText($content);
		}else{
			$this->setText($this->_content);
		}

		$appOwner->addClientAsset('js/ckeditor/ckeditor.js');
		$id = $this->getId();
		$this->setAttr('name',$id);
		$configs = JSON::encodeConfig($this->_configs->getConfigs('System'));
		$js = <<<EOT
		CKEDITOR.replace('$id',$configs);
EOT;
		$appOwner->addClientScript($js);
		return parent::prepareRender();
	}
}

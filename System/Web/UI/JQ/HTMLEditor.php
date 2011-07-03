<?php
class TJQHTMLEditor extends JQControl {

	function setValue($value) {
		$this->setText($value);
	}
	function __construct($id,$template) {
		parent::__construct($id,$template);
	}
	function prepareRender() {
		$this->setAttr('rows',8)
		->setAttr('cols',60)
		->setAttr('name',$this->getId());
	}
	function setToolBar($value) {
		if ($value=='all') {
			$this->removeConfig('toolbar');
			return $this;
		}
		return $this->setConfig('toolbar',$value,true);
	}
	function RenderScript($return = false) {
		$_tpl = $this->getTemplate();
		if (Request::isAJAXRequest()) {
			$sc1 = HTMLUtils::getJSAsset('ckeditor/ckeditor.js');
			$sc2 = HTMLUtils::getJSAsset('ckeditor/adapters/jquery.js');
			$id = $this->getId();
			$configs =JSON::encodeConfig($this->_configs);
			$retval = '<textarea '.$this->renderAttributes().'>';
			$retval .= $this->getText();
			$retval .= '</textarea>';//$this->getId();

			$retval .= <<<EOT
<script type="text/javascript">
	$(function() {
		$.getJS(['$sc1','$sc2'],function() {
				if (CKEDITOR.instances['$id']) {
                   CKEDITOR.remove(CKEDITOR.instances['$id']);
         		}
         		$('#$id').ckeditor($configs);
		});
	});
</script>
EOT;
			return $retval;
		}

		$_tpl->addAsset("ckeditor/ckeditor.js");
		$_tpl->addAsset("ckeditor/adapters/jquery.js");
		$retval = '<textarea '.$this->renderAttributes().'>';
		$retval .= $this->getText();
		$retval .= '</textarea>';//$this->getId();
		//window.CKEDITOR_BASEPATH=\''. BASE_URL .'/Data/js/ckeditor/\';
		$_tpl->addClientScript('if (CKEDITOR.instances[\''.$this->getId().'\']) {
           CKEDITOR.remove(CKEDITOR.instances[\''.$this->getId().'\']);
         };$(\'#'.$this->getId().'\').ckeditor('.JSON::encodeConfig($this->_configs).')');
		return $retval;
	}
}
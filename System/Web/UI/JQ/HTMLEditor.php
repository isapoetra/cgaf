<?php
namespace System\Web\UI\JQ;

use Request;
use System\Exceptions\SystemException;
use System\JSON\JSON;

class HTMLEditor extends Control implements \IContentEditor
{
    function setValue($value)
    {
        $this->setText($value);
    }

    function __construct($id)
    {
        parent::__construct($id);
        $this
            ->setConfig(
                array(
                    'customConfig' => '',
                    'baseHref' => BASE_URL . 'assets/js/ckeditor/',
                    'filebrowserImageBrowseUrl' => BASE_URL . '/asset/browse/?type=images',
                    'skin' => 'kama',
                    'uiColor' => '#9AB8F3',
                    'toolbarCanCollapse' => true,
                    'language' => $this->getAppOwner()->getLocale()->getLocale()));
    }

    function prepareRender()
    {
        $this->setAttr('rows', 8)->setAttr('cols', 60)->setAttr('name', $this->getId());
    }

    function setToolBar($value)
    {
        if ($value == 'all') {
            $this->removeConfig('toolbar');
            return $this;
        }
        return $this->setConfig('toolbar', $value, true);
    }

    function setContent($value)
    {
        if (is_string($value)) {
            $this->setText($value);
            return $this;
        }
        throw new SystemException('invalid content' . $value);
    }

    function RenderScript($return = false)
    {
        $appOwner = $this->getAppOwner();
        $configs = JSON::encodeConfig($this->_configs);
        if (Request::isAJAXRequest()) {
            $sc1 = $appOwner->getLiveAsset('js/ckeditor/ckeditor.js');
            $sc2 = $appOwner->getLiveAsset('js/ckeditor/adapters/jquery.js');
            $id = $this->getId();
            $retval = '<textarea ' . $this->renderAttributes() . '>';
            $retval .= $this->getText();
            $retval .= '</textarea>'; //$this->getId();
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
        $appOwner->addClientAsset('js/ckeditor/ckeditor.js');
        $appOwner->addClientAsset('js/ckeditor/adapters/jquery.js');
        $retval = '<textarea ' . $this->renderAttributes() . '>';
        $retval .= $this->getText();
        $retval .= '</textarea>';
        $appOwner->addClientScript('if (CKEDITOR.instances[\'' . $this->getId() . '\']) {
           CKEDITOR.remove(CKEDITOR.instances[\'' . $this->getId() . '\']);
         };$(\'#' . $this->getId() . '\').ckeditor(' . $configs . ')');
        return $retval;
    }
}

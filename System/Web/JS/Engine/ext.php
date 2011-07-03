<?php
using ("System.Web.UI.JExt");
class JSEngineExt extends JSBaseEngine {

	function __construct($appOwner) {
		parent::__construct ( $appOwner, 'ext', '3.3.1' );
	}
	protected function getJSAsset() {
		$prefix = strtolower ( $this->_baseConfig );
		$assets = array (
		//'js/'.$prefix.'/'.$this->_info['version'].
		 'resources/css/ext-all.css',
		 'ext-core-all.js',
		 'ext-all-no-core.js');
		if ($this->getConfig('useui',true)) {
			//$assets[] = 'ui/'.$prefix . '-ui.js';
			//$assets[] ='themes/base/'.$prefix.'-ui.css';
		}
		return $assets;
	}

	function renderScript($s) {
		return <<< EOT
				<script type="text/javascript" language="javascript">
				 Ext.onReady(function(){
				 $s
					});
				</script>
EOT;
	}
}
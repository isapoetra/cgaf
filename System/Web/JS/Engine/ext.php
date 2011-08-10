<?php
namespace System\Web\JS\Engine;

class Ext extends AbstractJSEngine {
	function __construct($appOwner) {
		parent::__construct($appOwner, 'ext', '3.3.1');
	}

	protected function getJSAsset() {
		$prefix = strtolower($this -> _baseConfig);
		$assets = array('resources/css/ext-all-notheme.css',
			'resources/css/' . $this -> getConfig('themes', 'xtheme-blue').'.css',
			'ext-extended.css',
			'adapter/ext/ext-base.js',
			'ext-all'.(CGAF_DEBUG  ? '-debug' : '').'.js',
			'cgaf/cgaf-ext.assets');
		if($this -> getConfig('useui', true)) {
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

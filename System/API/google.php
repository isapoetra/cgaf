<?php
namespace System\API;
//TODO move to System.Web.JS.API

class google extends PublicAPI {

	private $_apijs = array('plusone' => 'https://apis.google.com/js/plusone.js',);

	public function plusOne($size = 'small') {
		$size = $size ? $size : $this->getConfig("plusOne.size");
		return '<g:plusone size="' . $size . '"></g:plusone>';
	}

	public function init($service) {
		switch (strtolower($service)) {
		case "analitycs":
			break;
		}
	}

	public function initPlusOne() {
		AppManager::getInstance()->addClientAsset('https://apis.google.com/js/plusone.js');
	}

	public function initJS() {
		$key = AppManager::getInstance()->getConfig('google.jsapi.key');
		if (!$key) {
			throw new SystemException("invalid google api key");
		}
		AppManager::getInstance()->addClientAsset('https://www.google.com/jsapi?key=' . $key);
	}

	public function analitycs() {
		$gag = $this->getConfig('analytics.alitycsid');
		if ($gag) {
			$script = <<< SC
var _gaq = _gaq || [];
_gaq.push(['_setAccount', '$gag']);
_gaq.push(['_trackPageview']);
(function() {
    var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
    ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
})();
SC;
			AppManager::getInstance()->addClientScript($script);
		}
	}
}

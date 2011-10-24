<?php
namespace System\API;
//TODO move to System.Web.JS.API
use System\Exceptions\SystemException;
use \AppManager;
class google extends PublicApi {
	function __construct() {
		parent::__construct();
		$this->_apijs = array(
				'plusone' => \URLHelper::getCurrentProtocol() . '://apis.google.com/js/plusone.js');
	}
	public function plusOne($size = 'small') {
		$size = $size ? $size : 'small';
		$this->init(__FUNCTION__);
		if (is_array($size)) {
			$size = isset($size['size']) ? $size['size'] : 'small';
		}
		$size = $size ? $size : $this->getConfig("plusOne.size");
		return '<g:plusone size="' . $size . '"></g:plusone>';
	}
	public function initJS() {
		static $init;
		if ($init)
			return;
		$init = true;
		$key = AppManager::getInstance()->getConfig('service.google.jsapi.key');
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

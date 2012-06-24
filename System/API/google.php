<?php
namespace System\API;
// TODO move to System.Web.JS.API
using ( 'Libs.Google' );
use System\Auth\Auth;

use System\Web\JS\CGAFJS;
use System\Web\Utils\HTMLUtils;
use System\JSON\JSON;
use System\Exceptions\SystemException;
use AppManager;
class google extends PublicApi {
	function __construct() {
		parent::__construct ();
		$this->_apijs = array (
				'plusone' => \URLHelper::getCurrentProtocol () . '://apis.google.com/js/plusone.js'
		);
	}
	function getUserInfo($id) {
		$retval = new \stdClass();
		$retval->valid=true;
		$instance = Auth::getProviderInstance('google');
		$client = \GoogleAPI::getAuthInstance();
		$retval->loginURL= $client->createAuthUrl();

		if ($client->getAccessToken()) {
			$plus = \GoogleAPI::getPlusService();
			try {
				$people =new \stdClass();
				\Utils::toObject($plus->people->get($id),$people);
				$retval->displayName =  $people->displayName;
				$retval->profileURL = $people->url;
				$retval->imageURL = ($people->image ?$people->image['url'] : null);
				//$retval->activities = $plus->activities->listActivities('me', 'public');
			}catch (\Exception $e) {
				$retval->valid=false;
				$retval->_error = $e->getMessage();
			}
		}else{
			$retval->valid = false;
		}
		return $retval;
	}
	public function plusOne($size = 'small') {
		$size = $size ? $size : 'small';
		$this->init ( __FUNCTION__ );
		if (is_array ( $size )) {
			$size = isset ( $size ['size'] ) ? $size ['size'] : 'small';
		}
		$size = $size ? $size : $this->getConfig ( "plusOne.size" );
		return '<div class="g-plusone" size="' . $size . '"></div>';
	}
	public function initJS() {
		static $init;
		if ($init)
			return;
		$init = true;
		$key = AppManager::getInstance ()->getConfig ( 'google.jsapi.key' );
		if (! $key) {
			throw new SystemException ( "invalid google api key" );
		}
		AppManager::getInstance ()->addClientAsset ( \URLHelper::getCurrentProtocol () . '://www.google.com/jsapi?key=' . $key );
	}
	public function loadGoogleJS($js, $v, $configs) {
	}
	public function map($configs) {
		$this->initJS ();
		$app = AppManager::getInstance ();
		$configs = $configs ? $configs : array ();
		$g = $this->getConfig ( 'map', array (
				'sensor' => 'false',
				'key' => $app->getConfig ( 'service.google.maps.key' )
		) );
		\Utils::arrayMerge ( $g, $configs );
		$configs = json_encode ( $g );
		$js = <<<EOT
cgaf.getJSAsync('http://maps.googleapis.com/maps/api/js',$configs);
EOT;
		$app->addClientScript ( $js );
	}
	public function gplus($params) {
		$id= $params->id;
		$s = <<< SC
window.___gcfg = {lang: 'en'};
(function()
{var po = document.createElement("script");
po.type = "text/javascript"; po.async = true;po.src = "https://apis.google.com/js/plusone.js";
var s = document.getElementsByTagName("script")[0];
s.parentNode.insertBefore(po, s);
})();
SC;
		$params = HTMLUtils::renderAttr ( $params );
		// CGAFJS::loadAsync();
		AppManager::getInstance()->addMetaHeader('gplus',array(
				'href'=>'https://plus.google.com/'.$id,
				'rel'=>'publisher'
		),'link');
		AppManager::getInstance ()->addClientDirectScript ( $s );
		$ret = '<div class="g-plus" data-href="https://plus.google.com/'.$id.'?rel=publisher"	'.$params.'></div>';
		return $ret;
	}
	public function analitycs() {
		$gag = $this->getConfig ( 'analytics.alitycsid' );
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
			AppManager::getInstance ()->addClientScript ( $script );
		}
	}
}

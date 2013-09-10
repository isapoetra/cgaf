<?php
namespace System\API;
// TODO move to System.Web.JS.API
use AppManager;
use System\Auth\Auth;
use System\Exceptions\SystemException;
use System\JSON\JSON;
use System\Web\JS\CGAFJS;
use System\Web\Utils\HTMLUtils;

using('Libs.Google');

class google extends PublicApi
{
    function __construct()
    {
        parent::__construct();
        $this->_apijs = array(
            'plusone' => \URLHelper::getCurrentProtocol() . '://apis.google.com/js/plusone.js'
        );
    }

    function getUserInfo($id)
    {
        $retval = new \stdClass();
        $retval->valid = true;
        $instance = Auth::getProviderInstance('google');
        $client = \GoogleAPI::getAuthInstance();
        $retval->loginURL = $client->createAuthUrl();

        if ($client->getAccessToken()) {
            $plus = \GoogleAPI::getPlusService();
            try {
                $people = new \stdClass();
                \Utils::toObject($plus->people->get($id), $people);
                $retval->displayName = $people->displayName;
                $retval->profileURL = $people->url;
                $retval->imageURL = ($people->image ? $people->image['url'] : null);
                //$retval->activities = $plus->activities->listActivities('me', 'public');
            } catch (\Exception $e) {
                $retval->valid = false;
                $retval->_error = $e->getMessage();
            }
        } else {
            $retval->valid = false;
        }
        return $retval;
    }

    public function plusOne($size = 'small')
    {
        $size = $size ? $size : 'small';
        $this->init(__FUNCTION__);
        self::initgplus();
        if (is_array($size)) {
            $size = isset ($size ['size']) ? $size ['size'] : 'small';
        }
        $size = $size ? $size : $this->getConfig("plusOne.size");
        return '<div class="g-plusone" size="' . $size . '"></div>';
    }

    public function initJS()
    {
        static $init;
        if ($init)
            return;
        $init = true;
        $key = AppManager::getInstance()->getConfig('service.google.jsapi.key');
        if (!$key) {
            throw new SystemException ("invalid google api key");
        }
        $app = AppManager::getInstance();
        $app->addClientDirectScript('window.___gcfg = {lang: \'' . $app->getLocale()->getLocale() . '\'};');
        $app->addClientAsset(\URLHelper::getCurrentProtocol() . '://www.google.com/jsapi?key=' . $key);
    }

    public function loadGoogleJS($js, $v, $configs)
    {
    }

    public function map($configs)
    {
        $this->initJS();
        $app = AppManager::getInstance();
        $configs = $configs ? $configs : array();
        $g = $this->getConfig('map', array(
            'sensor' => 'false',
            'key' => $app->getConfig('service.google.maps.key')
        ));
        \Utils::arrayMerge($g, $configs);
        $configs = json_encode($g);
        $js = <<<EOT
cgaf.getJSAsync('http://maps.googleapis.com/maps/api/js',$configs);
EOT;
        $app->addClientScript($js);
    }

    private static function initgplus()
    {
        static $init;
        if ($init) return;
        $init = true;
        $s = <<< SC
(function()
{var po = document.createElement("script");
po.type = "text/javascript"; po.async = true;po.src = "https://apis.google.com/js/plusone.js";
var s = document.getElementsByTagName("script")[0];
s.parentNode.insertBefore(po, s);
})();
SC;
        AppManager::getInstance()->addClientDirectScript($s);
    }

    public function follow($params)
    {
        self::initgplus();
        $params = \Convert::toObject($params);
        $id = $params->id;
        if (!is_numeric($id)) throw new \InvalidArgumentException('required integer parameter for google id');
        $params->{'data-href'} = '//plus.google.com/' . $id;
        $params->{'data-rel'} = 'author';
        if (!isset($params->{'data-annotation'}))
            $params->{'data-annotation'} = 'none';
        if (!isset($params->{'data-height'}))
            $params->{'data-height'} = '20';
        unset($params->{'data-theme'});
        $params = HTMLUtils::renderAttr($params);
        return '<div class="g-follow" ' . $params . '"></div>';

    }

    public function gplus($params)
    {
        $params = \Convert::toObject($params);
        if (!isset($params->id)) return null;
        $id = $params->id;
        self::initgplus();
        $params->{'data-href'} = 'https://plus.google.com/' . $id . '?rel=publisher';
        $params = HTMLUtils::renderAttr($params);

        AppManager::getInstance()->addMetaHeader('gplus', array(
            'href' => 'https://plus.google.com/' . $id,
            'rel' => 'publisher'
        ), 'link');
        $ret = '<div class="g-plusone" ' . $params . '></div>';
        return $ret;
    }

    public function person($params)
    {
        $params = $this->mergeParams($params, __FUNCTION__);
        if (!isset($params->id)) return null;
        $id = $params->id;
        self::initgplus();
        //$params->{'data-layout'} ="landscape";

        $params->{'data-href'} = 'https://plus.google.com/' . $id . '?rel=publisher';
        $params = HTMLUtils::renderAttr($params);
        return '<div class="g-person" ' . $params . '></div>';
    }

    public function analitycs()
    {
        $gag = $this->getConfig('google.analytics.account');
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

    private function mergeParams($params, $f)
    {
        $retval = \Convert::toObject($params);
        $objs = $this->_config->getConfigs($f);
        if ($objs) {
            foreach ($objs as $k => $v) {
                if (!isset($retval->{$k})) {
                    $retval->{$k} = $v;
                }
            }
        }
        return $retval;
    }
}

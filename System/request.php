<?php
use System\Applications\IApplication;
use System\Session\Session;
use System\Web\ClientInfo;

if (!defined("CGAF"))
    die("Restricted Access");

abstract class Request
{
    private static $_instance;
    private static $_ajax;
    private static $_isJSONRequest;
    private static $_isXMLRequest;
    private static $_isDataRequest;
    private static $_clientInfo;
    private static $_isMobile;

    /**
     * Enter description here...
     *
     * @return IRequest
     */
    public static function getInstance()
    {
        if (!self::$_instance) {
            $class = '\\System\\' . CGAF_CONTEXT . "\\Request";
            self::$_instance = new $class();
            if (self::get('__mobile')) {
                self::isMobile(true);
            }
        }
        return self::$_instance;
    }

    public static function gets($place = null, $secure = true, $ignoreEmpty = true, $outArray = true)
    {
        $retval = self::getInstance()->gets($place, $secure, $ignoreEmpty);
        if ($outArray) {
            return $retval;
        }
        $o = new \stdClass();
        \Convert::toObject($retval, $o, true);
        return $o;
    }

    public static function getIgnore($ignored, $secure = true, $place = null, $ignoreEmpty = false)
    {
        $req = self::gets($place, $secure, $ignoreEmpty);
        $retval = array();
        if (is_string($ignored)) {
            $ignored = array(
                $ignored
            );
        }
        foreach ($req as $k => $v) {
            if (!in_array($k, $ignored)) {
                $retval[$k] = $v;
            }
        }
        return $retval;
    }

    public static function isMobile($value = null)
    {
        if ($value !== null) {
            self::$_isMobile = $value;
        } else {
            if (self::$_isMobile === null) {
                self::$_isMobile = \Convert::toBool(self::getClientInfo()->isMobile());
            }
        }
        return self::$_isMobile;
    }

    public static function isAndroid()
    {
        return self::getClientInfo()->get("platform", null)=='android';
    }

    public static function getClientPlatform()
    {
        $c = self::getClientInfo();
        return $c->getPlatform();
    }

    public static function isAJAXRequest($value = null)
    {
        if ($value !== null) {
            self::$_ajax = $value;
        }
        if (self::$_ajax == null) {
            self::$_ajax = (isset($_SERVER["HTTP_X_REQUESTED_WITH"]) && $_SERVER["HTTP_X_REQUESTED_WITH"] === "XMLHttpRequest") || self::get("_ajax") || self::get("__ajax") || self::isJSONRequest();
        }
        return self::$_ajax;
    }

    public static function get($varName, $default = null, $secure = true, $place = null)
    {
        return self::getInstance()->get($varName, $default, $secure, $place);
    }

    public static function set($varName, $value)
    {
        if ($varName === '__data') {
            self::isDataRequest(true);
        }
        return self::getInstance()->set($varName, $value);
    }

    public static function getOrigin()
    {
        static $origin;
        if (!$origin) {
            $ori = self::getIgnore(array('CGAFSESS', '__devtoken', '__url', '__c', '__a'), false);
            $path = null;
            if (isset($_REQUEST['__url'])) {
                $path = $_REQUEST['__url'];
            }
            $origin = URLHelper::add(defined('APP_URL') ? APP_URL : BASE_URL, $path, $ori);
        }
        return $origin;

    }

    public static function isJSONRequest($value = null)
    {
        if ($value !== null) {
            self::$_isJSONRequest = $value;
            self::$_isDataRequest = true;
        }
        if (self::$_isJSONRequest === null) {
            $accept = isset($_SERVER["HTTP_ACCEPT"]) ? $_SERVER["HTTP_ACCEPT"] : null;
            self::$_isJSONRequest = strpos($accept, 'application/json') !== false || Request::get("__json") || Request::get("__data") === 'json' || Request::get("__s") === 'json';
        }
        //ppd($_SERVER);
        return self::$_isJSONRequest;
    }

    public static function isXMLRequest()
    {
        if (self::$_isXMLRequest === null) {
            self::$_isXMLRequest = (isset($_SERVER["HTTP_ACCEPT"]) && $_SERVER["HTTP_ACCEPT"] == "application/xml, text/xml, */*") || Request::get("__xml") || Request::get('__data', null, false) === 'xml';
        }
        return self::$_isXMLRequest;
    }

    public static function setDataRequest($value)
    {
        self::$_isDataRequest = $value;
    }

    public static function isDataRequest($value = null)
    {
        if ($value !== null) {
            self::isAJAXRequest(true);
            self::$_isDataRequest = true;
        } elseif (self::$_isDataRequest === null) {
            self::$_isDataRequest = self::isJSONRequest() || self::isXMLRequest() || self::get('__data') || self::get('__s');
        }
        return self::$_isDataRequest;
    }

    /**
     *
     * Enter description here ...
     * @return ClientInfo
     */
    public static function &getClientInfo()
    {
        //Session::set('__clientInfo',null);
        $ci = Session::get('__clientInfo');
        if ($ci && isset($_SERVER["HTTP_USER_AGENT"]) && $ci->agent !== $_SERVER["HTTP_USER_AGENT"]) {
            Session::set('__clientInfo', null);
        }
        if (!$ci) {
            $ci = new ClientInfo(Utils::makeDir(CGAF::getInternalStorage('browsecap', false), 0700, '*'), Utils::makeDir(CGAF::getInternalStorage('.cache/.browsecap', false), 0700, '*'));

            Session::set('__clientInfo', $ci);
        }

        return $ci;
    }

    public static function isIE()
    {
        return self::getClientInfo()->isIE();
    }

    public static function isSupport($key, $default = false)
    {
        $browser = self::getClientInfo();
        return $browser->get($key, $default);
    }

    public static function getClientId()
    {
        $ip = 'client' . crc32($_SERVER['REMOTE_ADDR'] . $_SERVER['HTTP_USER_AGENT']);
        return $ip;
    }

    public static function isOverlay()
    {
        return self::get('__overlay') !== null;
    }

    public static function getQueryParams($ignore, $returnstring = false)
    {
        $params = self::getInstance()->gets(null, true);
        $retval = array();
        foreach ($params as $k => $v) {
            if (!in_array($k, $ignore)) {
                $retval[$k] = $v;
            }
        }
        return $returnstring ? Utils::arrayImplode($retval, '=', '&') : $retval;
    }

    public static function getClientCountry($ipAddr)
    {

        //function to find country and city from IP address
        //Developed by Roshan Bhattarai http://roshanbh.com.np

        //verify the IP address for the
        ip2long($ipAddr) == -1 || ip2long($ipAddr) === false ? trigger_error("Invalid IP", E_USER_ERROR) : "";
        $ipDetail = array(); //initialize a blank array

        //get the XML result from hostip.info
        $xml = file_get_contents("http://api.hostip.info/?ip=" . $ipAddr);

        //get the city name inside the node <gml:name> and </gml:name>
        preg_match("@<Hostip>(\s)*<gml:name>(.*?)</gml:name>@si", $xml, $match);

        //assing the city name to the array
        $ipDetail['city'] = $match[2];

        //get the country name inside the node <countryName> and </countryName>
        preg_match("@<countryName>(.*?)</countryName>@si", $xml, $matches);

        //assign the country name to the $ipDetail array
        $ipDetail['country'] = $matches[1];

        //get the country name inside the node <countryName> and </countryName>
        preg_match("@<countryAbbrev>(.*?)</countryAbbrev>@si", $xml, $cc_match);
        $ipDetail['country_code'] = $cc_match[1]; //assing the country code to array

        //return the array containing city, country and country code
        return $ipDetail;


    }

    public static function getClientIp()
    {
        static $ip;
        if ($ip == null) {
            //check ip from share internet
            if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
                $ip = $_SERVER['HTTP_CLIENT_IP'];
            } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                //to check ip is pass from proxy
                $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
            } else {
                $ip = $_SERVER['REMOTE_ADDR'];
            }
        }
        return $ip;
    }

    public static function log(IApplication $app)
    {
        $ip = getClientIp();
        $db = Utils::ToDirectory($app->getInternalStoragePath() . "/log/counter/ip.db");
        $path = dirname($db);
        Utils::makeDir($path);
        $file_ip = fopen($db, 'w+');
        $found = false;
        while (!feof($file_ip)) {
            $line[] = fgets($file_ip, 1024);
        }
        for ($i = 0; $i < (count($line)); $i++) {
            list($ip_x) = explode("\n", $line[$i]);
            if ($ip == $ip_x) {
                $found = 1;
            }
        }
        fclose($file_ip);
        if (!($found == 1)) {
            $file_ip2 = fopen($db, 'ab');
            $line = "$ip\n";
            fwrite($file_ip2, $line, strlen($line));
            $file_count = @fopen($path . DS . "count.db", 'r+');
            if (!$file_count) {
                return;
            }
            $data = '';
            while (!feof($file_count))
                $data .= fread($file_count, 4096);
            fclose($file_count);
            @list($today, $yesterday, $total, $date, $days) = explode("%", $data);
            if ($date == date("Y m d"))
                $today++;
            else {
                $yesterday = $today;
                $today = 1;
                $days++;
                $date = date("Y m d");
            }
            $total++;
            $line = "$today%$yesterday%$total%$date%$days";
            $file_count2 = fopen($path . DS . 'count.db', 'wb');
            fwrite($file_count2, $line, strlen($line));
            fclose($file_count2);
            fclose($file_ip2);
        }
    }

    public static function getDefaultIgnore($ignores = array())
    {
        if (!$ignores) $ignores = array();
        return array_merge(array('__url', '__appId', '__data', 'CGAFSESS', '__c', '__a', '__data'), $ignores);
    }
}

?>

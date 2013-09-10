<?php
namespace System\Captcha;

use System\Applications\IApplication;
use System\Exceptions\SystemException;
use System\JSON\JSON;

class ReCaptchaResponse
{
    var $is_valid;
    var $error;
}

class Recaptcha extends AbstractCaptcha
{
    const API_SERVER = 'http://www.google.com/recaptcha/api';

    /**
     * URI to the secure API
     *
     * @var string
     */
    const API_SECURE_SERVER = 'https://www.google.com/recaptcha/api';

    /**
     * URI to the verify server
     *
     * @var string
     */
    const VERIFY_SERVER = 'www.google.com';
    private $_publicKey;
    private $_privateKey;
    private $_chalangeField;
    private $_responseField;

    function __construct(IApplication $appOwner)
    {
        parent::__construct('recaptcha', $appOwner);

    }

    protected function _initialize()
    {
        parent::_initialize();

        $this->_publicKey = $this->getConfig('publicKey');
        $this->_publicKey = $this->getConfig('privateKey');
        $this->_chalangeField = $this->getConfig('chalangeField', 'recaptcha_challenge_field');
        $this->_responseField = $this->getConfig('responseField', 'recaptcha_challenge_field');

    }

    private function _recaptcha_qsencode($data)
    {
        $req = "";
        foreach ($data as $key => $value)
            $req .= $key . '=' . urlencode(stripslashes($value)) . '&';

        // Cut the last '&'
        $req = substr($req, 0, strlen($req) - 1);
        return $req;
    }

    private function _post($host, $path, $data, $port = 80)
    {
        $req = $this->_recaptcha_qsencode($data);
        $http_request = "POST $path HTTP/1.0\r\n";
        $http_request .= "Host: $host\r\n";
        $http_request .= "Content-Type: application/x-www-form-urlencoded;\r\n";
        $http_request .= "Content-Length: " . strlen($req) . "\r\n";
        $http_request .= "User-Agent: reCAPTCHA/PHP\r\n";
        $http_request .= "\r\n";
        $http_request .= $req;

        $response = '';
        if (false == ($fs = @fsockopen($host, $port, $errno, $errstr, 10))) {
            die ('Could not open socket');
        }

        fwrite($fs, $http_request);

        while (!feof($fs))
            $response .= fgets($fs, 1160); // One TCP-IP packet
        fclose($fs);
        $response = explode("\r\n\r\n", $response, 2);

        return $response;

    }

    function validateRequest()
    {
        if (!$this->_privateKey) {
            die ("To use reCAPTCHA you must get an API key from <a href='https://www.google.com/recaptcha/admin/create'>https://www.google.com/recaptcha/admin/create</a>");
        }
        $remoteip = $_SERVER["REMOTE_ADDR"];
        if ($remoteip == null || $remoteip == '') {
            die ("For security reasons, you must pass the remote ip to reCAPTCHA");
        }


        $challenge = $_POST[$this->_chalangeField];
        $response = $_POST[$this->_responseField];

        //discard spam submissions
        if ($challenge == null || strlen($challenge) == 0 || $response == null || strlen($response) == 0) {
            $recaptcha_response = new ReCaptchaResponse();
            $recaptcha_response->is_valid = false;
            $recaptcha_response->error = 'incorrect-captcha-sol';
            return $recaptcha_response;
        }
        $extra_params = null;
        $response = $this->_post(self::VERIFY_SERVER, "/recaptcha/api/verify",
            array(
                'privatekey' => $this->_privateKey,
                'remoteip' => $remoteip,
                'challenge' => $challenge,
                'response' => $response
            ) + $extra_params
        );

        $answers = explode("\n", $response [1]);
        $recaptcha_response = new ReCaptchaResponse();

        if (trim($answers [0]) == 'true') {
            $recaptcha_response->is_valid = true;
        } else {
            $recaptcha_response->is_valid = false;
            $recaptcha_response->error = $answers [1];
        }
        return $recaptcha_response;

    }

    /**
     * @param bool $return
     * @return string
     * @throws SystemException
     */
    public function Render($return = false)
    {
        if (!$this->_publicKey) {
            throw new SystemException('Missing public key');
        }
        $host = self::API_SERVER;

        if (\URLHelper::getCurrentProtocol() === 'https') {
            $host = self::API_SECURE_SERVER;
        }

        $errorPart = '';
        $reCaptchaOptions = '';
        $opt = $this->getConfig('options');
        if ($this->_errorMessage) {
            $errorPart = "&amp;error=" . $this->_errorMessage;
        }


        if (!empty($opt)) {
            $encoded = JSON::encodeConfig($opt);
            $reCaptchaOptions = <<<SCRIPT
<script type="text/javascript">
    var RecaptchaOptions = {$encoded};
</script>
SCRIPT;
        }
        $retval = $reCaptchaOptions;
        //TODO Recheck google always return 404 error from localhost
        $retval .= '<script src="' . $host . '/challange?k=' . $this->_publicKey . $errorPart . '" type="text/javascript"></script>';

        $retval .= <<<HTML
<noscript>
   <iframe src="{$host}/noscript?k={$this->_publicKey}$errorPart"
       height="300" width="500" frameborder="0"></iframe>
   <textarea name="$this->_chalangeField" rows="3" cols="40">
   </textarea>
   <input type="hidden" name="{$this->_responseField}"
       value="manual_challenge"/>
</noscript>
HTML;
        return $retval;
    }

}

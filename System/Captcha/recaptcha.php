<?php
namespace System\Captcha;

class Recaptcha extends AbstractCaptcha {
	const API_SERVER = 'http://api.recaptcha.net';

	/**
	 * URI to the secure API
	 *
	 * @var string
	 */
	const API_SECURE_SERVER = 'https://api-secure.recaptcha.net';

	/**
	 * URI to the verify server
	 *
	 * @var string
	 */
	const VERIFY_SERVER = 'http://api-verify.recaptcha.net/verify';

	protected function _initialize() {
		$c = $this->_container;
		$c->setConfig('outputType', 'text');
	}

	/**
	 *
	 */
	public function renderImage() {
	}
	function getConfig($name, $def = null) {
		$ret = parent::getConfig('recaptcha.' . $name);
		if ($ret == null) {
			$ret = parent::getConfig($name, $def);
		}
		return $ret;
	}
	/**
	 *
	 */
	public function render() {
		CGAFDebugOnly();
		$pki = $this->getConfig('publicKey');
		if ($pki === null) {
			throw new SystemException('Missing public key');
		}
		$host = self::API_SERVER;

		if ((bool) $this->getConfig('ssl') === true) {
			$host = self::API_SECURE_SERVER;
		}

		$htmlBreak = '<br>';
		$htmlInputClosing = '>';

		if ((bool) $this->getConfig('xhtml', true) === true) {
			$htmlBreak = '<br />';
			$htmlInputClosing = '/>';
		}

		$errorPart = '';
		$err = $this->getConfig('error');
		if (!empty($err)) {
			$errorPart = '&error=' . urlencode($err);
		}

		$reCaptchaOptions = '';
		$opt = $this->getConfig('options');
		if (!empty($opt)) {
			$encoded = JSON::encodeConfig($opt);
			$reCaptchaOptions = <<<SCRIPT
<script type="text/javascript">
    var RecaptchaOptions = {$encoded};
</script>
SCRIPT;
		}

		$return = $reCaptchaOptions;
		$return .= <<<HTML
<script type="text/javascript"
   src="{$host}/challenge?k={$pki}{$errorPart}">
</script>
HTML;
		$return .= <<<HTML
<noscript>
   <iframe src="{$host}/noscript?k={$pki}{$errorPart}"
       height="300" width="500" frameborder="0"></iframe>{$htmlBreak}
   <textarea name="recaptcha_challenge_field" rows="3" cols="40">
   </textarea>
   <input type="hidden" name="recaptcha_response_field"
       value="manual_challenge"{$htmlInputClosing}
</noscript>
HTML;
		return $return;
	}

}

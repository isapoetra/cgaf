<?php
namespace System\Controllers;
use System\Exceptions\SystemException;

use System\MVC\Controller;
use System\Captcha\ICaptchaContainer;
use System\Configurations\Configuration;
use System\Session\Session;
class CaptchaController extends Controller implements ICaptchaContainer {
	static $V = array ("a", "e", "i", "o", "u", "y" );
	static $VN = array ("a", "e", "i", "o", "u", "y", "2", "3", "4", "5", "6", "7", "8", "9" );
	static $C = array ("b", "c", "d", "f", "g", "h", "j", "k", "m", "n", "p", "q", "r", "s", "t", "u", "v", "w", "x", "z" );
	static $CN = array ("b", "c", "d", "f", "g", "h", "j", "k", "m", "n", "p", "q", "r", "s", "t", "u", "v", "w", "x", "z", "2", "3", "4", "5", "6", "7", "8", "9" );

	private $_charset = 'ABCDEFGHKLMNPRSTUVWYZabcdefghklmnprstuvwyz23456789';
	private $_defaultOptions = array ('caseSensitive' => false, 'outputType' => 'image', 'useNumbers' => true, 'outputImageType' => 'jpeg', 'engine' => 'Zend.image', 'fontSize' => 24, 'wordLen' => 4, 'dotNoiseLevel' => 100, 'lineNoiseLevel' => 5, 'fontName' => 'fonts/automatic.gdf', 'useTTFFont' => false, 'TTFFont' => 'fonts/Vera.ttf', 'width' => 200, 'height' => 50, 'recaptcha' => array ('publicKey' => '' ) );
	private $_options;
	private $_font;
	private $_SID;
	private $_renderer;
	/**
	 *
	 * @param $appOwner IApplication
	 */
	function __construct($appOwner) {
		parent::__construct ( $appOwner, "captcha" );
		$this->_defaultOptions ['BackgroundDirectory'] = $appOwner->getInternalStorage("/captcha/bg/");

		$options = $appOwner->getConfig ( 'captcha', array () );
		$this->_options = new Configuration ( array_merge_recursive ( $options, $this->_defaultOptions ) );
		$this->_SID = $this->getConfig ( 'captcha.sessionid', $appOwner->getAppId () . 'Capcha' );
	}

	function isValidCaptcha($captchaId = null, $throw = true) {
		$captchaId = $captchaId ? $captchaId : "__captcha";
		$captcha = $this->getCaptcha ( $captchaId );

		$retval = ($captcha && ($captcha === \Request::get ( $captchaId )));
		if (! $captcha) {
			$captcha = $this->generateCode ();
			$this->setCaptcha ( $captchaId, $captcha );
		}
		if (! $retval && ($throw)) {
			throw new SystemException ( __ ( "error.invalidcaptcha", "Invalid Captcha" ) );
		}
		return $retval;
	}

	function setCaptcha($captchaId, $value) {
		$c = $this->getCaptcha ();
		$c->$captchaId = $value;
		Session::set ( $this->_SID, $c );
	}

	public function getCaptcha($id = null) {

		$captcha = Session::get ( $this->_SID );
		if (! $captcha) {
			$captcha = new \Object ();
			Session::set ( "Captcha", $captcha );
		}
		if ($id) {
			return $captcha->$id;
		}
		return $captcha;
	}

	function isAllow($access = "view") {
		switch ($access) {
			case "view" :
			case "html" :
				return true;
				break;
		}
		return parent::isAllow ( $access );
	}

	public function getConfig($configName, $def = null) {
		$retval = $this->_options->getConfig ( $configName );
		return $retval !== null ? $retval : parent::getConfig ( $configName, $def );
	}

	function getWordLen() {
		return $this->getConfig ( 'worldlen', 5 );
	}

	function __get($name) {
		return $this->getConfig ( $name, parent::__get ( $name ) );
	}

	function generateCode() {
		$code = '';
		$word = '';
		$wordLen = $this->wordLen;
		$vowels = $this->useNumbers ? self::$VN : self::$V;
		$consonants = $this->useNumbers ? self::$CN : self::$C;

		for($i = 0; $i < $wordLen; $i = $i + 2) {
			// generate word with mix of vowels and consonants
			$consonant = $consonants [array_rand ( $consonants )];
			$vowel = $vowels [array_rand ( $vowels )];
			$word .= $consonant . $vowel;
		}

		if (strlen ( $word ) > $wordLen) {
			$word = substr ( $word, 0, $wordLen );
		}
		if (! $this->caseSensitive) {
			$word = strtoupper ( $word );
		}
		return $word;
	}

	function getResource($name) {
		$search = array(
		$this->getAppOwner ()->getInternalData ( $name ),
		\CGAF::getInternalStorage(dirname($name),false,false).DS.basename($name)
		);
		foreach($search as $s) {
			if (is_file($s)) {
				return $s;
			}
		}
		throw new SystemException('unable to get font file : '.$name);
		return null;
	}

	function get($capchaId = null) {
		return $this->Index ();
	}

	/**
	 * @return ICaptchaRenderer
	 */
	private function getRenderer() {
		if (! $this->_renderer) {
			$e = $this->_options->getConfig ( 'engine', 'default' );
			$c = 'System\\Captcha\\' .  str_replace('.', '\\', $e) ;
			if (class_exists($c)) {
				$this->_renderer = new $c ( $this );
			}
		}
		return $this->_renderer;
	}

	function getFont($mode = null) {
		if (! is_int ( $this->_font )) {
			//is a file name
			if ($this->getConfig ( 'useTTFFont' )) {
				$ffile = $this->getConfig ( 'TTFFont' );

				if (is_file ( $ffile )) {
					return $ffile;
				} else {
					$ffile =  $this->getResource ( $ffile );

					return $ffile;
				}
			} else {
				$font = @imageloadfont ( $this->getResource ( $this->getConfig ( 'fontName' ) ) );
			}
		}
		if (empty ( $font )) {
			throw new SystemException ( "Image CAPTCHA requires font" );
		}
		return $this->_font;

	}

	function renderContainer($captchaId = "__captcha", $attr = null, $showlabel = true) {
		static $rendered;
		if (! $rendered) {
			$rendered = array ();
		}
		$reset = ! in_array ( $captchaId, $rendered );
		$captchaId = $captchaId ? $captchaId : "__captcha";
		$url = BASE_URL . "/captcha/get/__captchaId/$captchaId/";
		$captchaId = $captchaId ? $captchaId : "__captcha";
		$imgId = "img{$captchaId}" . mt_rand ();
		$attr .= " src=\"$url\"";
		$cid = "captcha-" . time ();
		$retval = "<div id=\"{$cid}\" class=\"captcha\" >";

		$retval .= '<div class="content"><div class="wrap">' . $this->html ( $captchaId, $reset ) . '</div>';
		$retval .= "<a class=\"reload\" href=\"#\" onclick=\"$('#$cid > div > div').load('" . BASE_URL . "/captcha/html/?__captchaReset=1&_t'+Math.random());return true;\">&nbsp;</a>";
		$retval .= '</div>';
		if ($showlabel) {
			$retval .= "<label>" . __ ( "captcha", "Captcha" );
		}
		$retval .= "<input type=\"text\" id=\"$captchaId\" name=\"$captchaId\" class=\"required\" autocomplete=\"off\"/>";
		if ($showlabel) {
			$retval .= "</label>";
		}
		$retval .= "</div>";
		$rendered [] = $captchaId;
		return $retval;
	}

	public function html($capchaId = '__captcha', $reset = null) {
		$capchaId = is_array ( $capchaId ) ? '__captcha' : $capchaId;

		$reset = $reset !== null ? $reset : Request::get ( "__captchaReset", true );
		$captcha = $this->getCaptchaCode ( $capchaId, $reset );
		$renderer = $this->getRenderer ();
		$renderer->setCode ( $captcha );
		$retval = '';
		switch ($this->outputType) {
			case 'image' :
				$im = $renderer->renderImage ();
				$tmpname = tempnam ( '', 'captcha' );
				$mime = 'data:image/png';
				switch ($this->outputImageType) {
					case 'jpeg' :
						$mime = 'data:image/jpeg';
						imagejpeg ( $im, $tmpname );
						break;
					case 'png' :
						imagepng ( $im, $tmpname );
						break;
				}

				$c = file_get_contents ( $tmpname );
				$retval = '<img src="' . $mime . ';base64,' . base64_encode ( $c ) . '"/>';
				unlink ( $tmpname );
				break;
			default :
				$retval = $renderer->render ();
		}
		return $retval;
	}

	function getCaptchaCode($capchaId, $reset = false) {
		$captcha = $this->getCaptcha ( $capchaId );
		if ($reset) {
			$captcha = $this->generateCode ();
		} else {
			if (! $captcha) {
				$captcha = $this->generateCode ();
			}
		}
		$this->SetCaptcha ( $capchaId, $captcha );
		return $captcha;
	}

	/**
	 *
	 */
	function Index() {

		$capchaId = Request::Get ( "__captchaId", "__captcha" );
		$mode = Request::get ( "__captchaMode", "image" );
		$captcha = $this->getCaptchaCode ( $capchaId, Request::get ( "__captchaReset", true ) );
		$renderer = $this->getRenderer ();
		$renderer->setCode ( $captcha );
		header ( "Expires: Mon, 26 Jul 1997 05:00:00 GMT" );
		header ( "Last-Modified: " . gmdate ( "D, d M Y H:i:s" ) . "GMT" );
		header ( "Cache-Control: no-store, no-cache, must-revalidate" );
		header ( "Cache-Control: post-check=0, pre-check=0", false );
		header ( "Pragma: no-cache" );

		switch ($this->outputType) {
			case 'image' :
				if (! extension_loaded ( "gd" )) {
					throw new SystemException ( "Image CAPTCHA requires GD extension" );
				}

				if (! function_exists ( "imagepng" )) {
					throw new SystemException ( "Image CAPTCHA requires PNG support" );
				}

				if (! function_exists ( "imageftbbox" )) {
					throw new SystemException ( "Image CAPTCHA requires FT fonts support" );
				}
				$im = $renderer->renderImage ();
				switch ($this->outputImageType) {
					case 'jpeg' :
						header ( "Content-Type: image/jpeg" );
						imagejpeg ( $im );
						break;
					case 'png' :
					default :
						header ( "Content-Type: image/png" );
						imagepng ( $im );
				}
				break;
			default :
				return $renderer->render ();
		}
		exit ( 0 );
	}

	/**
	 * @param unknown_type $configName
	 * @param unknown_type $value
	 */
	public function setConfig($configName, $value) {
		$this->_options->setConfig ( $configName, $value );
	}

	/**
	 *
	 */
	public function getConfigs() {
		return $this->_options;
	}

}

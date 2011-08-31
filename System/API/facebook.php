<?php
namespace System\API;
use System\Template\TemplateHelper;
use \Utils;
use System\Web\Utils\HTMLUtils;
use \CGAF;
using('Libs.facebook');
class facebook extends PublicApi {
	private $_fb;
	function __construct($config = array()) {
		parent::__construct($config);
		$this->_fb = \FBUtils::getInstance();
		$this->init(null);
	}
	function init($service) {
		\FBUtils::initJS();
	}
	function like($config = null) {
		self::init(__FUNCTION__);
		$config = $config ? $config : array();
		$def = array('layout' => 'button_count', 'send'=>'true','font'=>'segoe ui','show_faces' => true, 'width'=>"150", 'colorsheme' => 'dark');
		//send="true" layout="button_count"  show_faces="true" font="segoe ui"
		$config = \Utils::arrayMerge($config, $def);
		if (!array_key_exists('href', $config)) {
			$config['href'] = \URLHelper::getOrigin();
		}
		return '<fb:like ' . HTMLUtils::renderAttr($config) . '></fb:like>';
	}
	function send($config) {
		$config = Utils::arrayMerge(array(), $config);
		if (!array_key_exists('href', $config)) {
			return null;
		}
		return '<fb:send ' . HTMLUtils::renderAttr($config) . '></fb:send>';
	}
	function comments($config) {
		$config = Utils::arrayMerge(array('num_post' => 2, 'width' => 500), $config);
		if (!array_key_exists('href', $config)) {
			return null;
		}
		return '<fb:comments ' . HTMLUtils::renderAttr($config) . '></fb:comments>';
	}
	function activity($config) {
		$config = Utils::arrayMerge(array('width' => 300, 'height' => 300, 'header' => true), $config);
		if (!array_key_exists('href', $config)) {
			return null;
		}
		return '<fb:activity ' . HTMLUtils::renderAttr($config) . '></fb:activity>';
	}
	function recommendations($config) {
		$config = Utils::arrayMerge(array('width' => 300, 'height' => 300, 'header' => true), $config);
		if (!array_key_exists('site', $config)) {
			return null;
		}
		return '<fb:recommendations ' . HTMLUtils::renderAttr($config) . '></fb:recommendations>';
	}
	function loginbutton($config = array()) {
		return $this->getAppOwner()->renderView('fb/status', null, array('fb' => $this->_fb), 'auth');
		if ($user) {
			//return 'Your user profile is'.htmlspecialchars(print_r($user_profile, true));
		} else {
			return '<a href="' . $url . '">Login</a>';
			$def = array(
					'width' => 300,
					'height' => 300,
					'header' => true);
			$config = Utils::arrayMerge($def, $config);
			return '<fb:login-button ' . HTMLUtils::renderAttr($config) . '></fb:login-button>';
		}
	}
	function facepile($config) {
		$config = Utils::arrayMerge(array('width' => 300, 'height' => 300, 'max_rows' => 1), $config);
		if (!array_key_exists('href', $config)) {
			return null;
		}
		return '<fb:facepile' . HTMLUtils::renderAttr($config) . '></fb:facepile>';
	}
}

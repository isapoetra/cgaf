<?php
using ( dirname ( __FILE__ ) . DS . 'facebook/src/' );
abstract class FBUtils {
	private static $_instance;
	public static function getInstance() {
		if (! self::$_instance) {
			$app = AppManager::getInstance ();
			self::$_instance = new Facebook ( array (
					'appId' => $app->getConfig ( 'service.facebook.AppId' ),
					'secret' => $app->getConfig ( 'service.facebook.secret' )
			) );
			self::initJS ();
		}
		return self::$_instance;
	}
	public static function getUser() {
		return self::getInstance ()->getUser ();
	}
	public static function getUserProfile() {
		static $user_profile;
		if ($user_profile === NULL) {
			$user_profile = array ();
			$fb = self::getInstance ();
			$user = $fb->getUser ();
			if ($user) {
				try {
					// Proceed knowing you have a logged in user who's
					// authenticated.
					$user_profile = $fb->api ( '/me' );
				} catch ( \FacebookApiException $e ) {
					// echo '<pre>'.htmlspecialchars(print_r($e,
					// true)).'</pre>';
					$user = null;
				}
			}
		}
		return $user_profile;
	}
	public static function initJS() {
		static $init;
		if ($init)
			return;
		$init = true;
		$app = \AppManager::getInstance ();
		$chanel = BASE_URL . 'channel.html';
		$appId = self::getInstance ()->getAppID ();
		$loginurl = BASE_URL . '/auth/';
		$script = <<< EOT
	var r =  $('#fb-root');
	if (r.length ===0) {
		$('<div id="fb-root"></div>').prependTo('body');
	}
	(function(d, s, id) {
	  var js, fjs = d.getElementsByTagName(s)[0];
	  if (d.getElementById(id)) {return;}
	  js = d.createElement(s);
	  js.id = id;
	  js.src = "//connect.facebook.net/en_US/all.js#xfbml=1&appId: '$appId'";
	  fjs.parentNode.insertBefore(js, fjs);
	}(document, 'script', 'facebook-jssdk'));
EOT;
		$app->addClientDirectScript ( $script );
	}
}

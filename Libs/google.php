<?php
use System\Exceptions\SystemException;

abstract class GoogleAPI {
	private static $_init;
	private static $_apiPath;
	private static $_apiClient;
	const baseConfig ='auth.External.google';
	private static $_plusInstance;
	private static $_oAuth2Instance;
	private static function init() {
		if (self::$_init) {
			return true;
		}
		self::$_apiPath = \Utils::ToDirectory ( dirname ( __FILE__ ) . '/google/google-api-php-client/src/' );
		/*$f = self::$_apiPath . 'local_config.php';
		 $s = array ();
		$s [] = '<?php global $apiConfig;';
		$s [] = '$apiConfig = array(';
				$configs = CGAF::getConfigs ( 'auth.External.google' );
				foreach ( $configs as $k => $v ) {
				$s [] = '\''.$k . '\'=>\'' . $v.'\',';
				}
				$s [] = ');';
		$s [] = '?>';

		if ((is_file($f) && ! is_writable($f)) || (!is_file($f) && !is_writable(dirname($f)))) {
		throw new SystemException('Configuration File not writable '.$f);
		}
		file_put_contents ( $f, implode ( PHP_EOL, $s ) );*/
	}
	private static function getConfig($name) {
		return \CGAF::getConfig(self::baseConfig.'.'.$name);
	}
	public static function &getAuthInstance() {
		if (self::$_apiClient) {
			return self::$_apiClient;
		}
		self::init ();
		\CGAF::Using ( self::$_apiPath . 'apiClient.php' );
		$cl= new apiClient ();
		$cl->setClientId(self::getConfig('client_id'));
		$cl->setApplicationName(self::getConfig('application_name'));
		$cl->setClientSecret(self::getConfig('client_secret'));
		$cl->setRedirectUri(self::getConfig('redirect_uri'));
		$cl->setDeveloperKey(self::getConfig('developer_key'));
		self::$_apiClient = $cl;
		return self::$_apiClient;
	}
	public static function getPlusService() {
		if (self::$_plusInstance===null) {
			CGAF::Using ( self::$_apiPath . 'contrib/apiPlusService.php' );
			$client = self::getAuthInstance();
			if ($client->getAccessToken()) {
				self::$_plusInstance = new apiPlusService($client);
			}
		}
		return self::$_plusInstance;
	}
	public static function getOAuth2Instance() {
		if (!self::$_oAuth2Instance){
			self::init ();
			\CGAF::Using ( self::$_apiPath . 'contrib/apiOauth2Service.php' );
			self::$_oAuth2Instance= new apiOauth2Service(self::getAuthInstance());
		}
		return self::$_oAuth2Instance;
	}
}
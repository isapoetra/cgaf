<?php
abstract class GoogleAPI {
	private static $_init;
	private static $_apiPath;
	private static $_apiClient;
	private static function init() {
		if (self::$_init) {
			return true;
		}
		self::$_apiPath = \Utils::ToDirectory ( dirname ( __FILE__ ) . '/google/google-api-php-client/src/' );
		$f = self::$_apiPath . 'local_config.php';
		$s = array ();
		$s [] = '<?php global $apiConfig;';
		$s [] = '$apiConfig = array(';
		$configs = CGAF::getConfigs ( 'auth.External.google' );
		foreach ( $configs as $k => $v ) {
			$s [] = '\''.$k . '\'=>\'' . $v.'\',';
		}
		$s [] = ');';
		$s [] = '?>';
		file_put_contents ( $f, implode ( PHP_EOL, $s ) );
	}
	public static function getAuthInstance() {
		if (self::$_apiClient) {
			return self::$_apiClient;
		}
		self::init ();
		\CGAF::Using ( self::$_apiPath . 'apiClient.php' );
		self::$_apiClient = new apiClient ();
		return self::$_apiClient;
	}
	public static function getOAuth2Instance($api) {
		self::init ();
		//\CGAF::Using ( self::$_apiPath . 'apiClient.php' );
		\CGAF::Using ( self::$_apiPath . 'contrib/apiOauth2Service.php' );
		return new apiOauth2Service($api);
	}
}
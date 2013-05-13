<?php
namespace System\Auth;

using('libs.openid.Auth.OpenID');
final class OpenId {
	private static $_consumer;

	private static function getStore() {

		$store_path = \CGAF::getInternalStorage('openid/consumer/', false, true);

		if (!file_exists($store_path) && !mkdir($store_path)) {
			print "Could not create the FileStore directory '$store_path'. " . " Please check the effective permissions.";
			exit(0);
		}
		$r = new Auth_OpenID_FileStore($store_path);

		return $r;
	}
	public static function getConsumer() {
		if (!self::$_consumer) {

			self::$_consumer = new Auth_OpenID_Consumer(self::getStore());
		}
		return self::$_consumer;
	}

}

?>
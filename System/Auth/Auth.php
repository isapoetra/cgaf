<?php
namespace System\Auth;
use System\Exceptions\SystemException;
use System\API\PublicApi;
use System\Web\Utils\HTMLUtils;
use \URLHelper;
use \AppManager;
class Auth {
	const FACEBOOK = 'facebook';
	const NEED_RETRY_STATE = 'state_need_retry';
	const NEED_REMOTEAUTH_STATE = 'state_need_remoteauth';
	const NEED_CONFIRM_LOCAL_STATE='state_need_confirm_local_auth';
	private static $_providerInstance = array();
	/**
	 *
	 * get Authentificator instance
	 * @param string $id
	 * @param bool $newInstance
	 * @throws SystemException
	 * @return System\Auth\IAuthProvider
	 */
	public static function getProviderInstance($id, $newInstance = false) {
		$cname = '\\System\\Auth\\Providers\\' . $id;
		$instance = null;
		\CGAF::LoadClass($cname, false);
		if (class_exists($cname, false)) {
			$instance = new $cname(\AppManager::getInstance());
			if (!$instance instanceof IAuthProvider) {
				throw new SystemException('Invalid Authentification method');
			}
		}
		return $instance;
	}
}

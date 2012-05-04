<?php
namespace System\Auth;
use System\Exceptions\SystemException;
use System\API\PublicApi;
use System\Web\Utils\HTMLUtils;
use \URLHelper;
use \AppManager;
class Auth {
	const FACEBOOK = 'facebook';
	const ERROR_STATE = 'state_error';
	const NEED_RETRY_STATE = 'state_need_retry';
	const NEED_REMOTEAUTH_STATE = 'state_need_remoteauth';
	const NEED_CONFIRM_LOCAL_STATE = 'state_need_confirm_local_auth';
	private static $_providerInstance = array ();
	/**
	 * get Authentificator instance
	 *
	 * @param $id string
	 * @param $newInstance bool
	 * @throws SystemException
	 * @return System\Auth\IAuthProvider
	 */
	public static function getProviderInstance($id, $newInstance = false) {
		if (!$id) {
			throw new SystemException ( 'Invalid Authentification method' );
		}
		$cname = '\\System\\Auth\\Providers\\' . $id;
		$instance = null;
		try {
		$instance = new $cname ( \AppManager::getInstance () );
		}catch(\Exception $e) {
			return null;
		}
		if (! $instance instanceof IAuthProvider) {
			throw new SystemException ( 'Invalid Authentification method' );
		}
		if (! $instance->initialize ()) {
			throw new SystemException ( 'unable to initialize ' . $cname . ' ' . $instance->getLastError () );
			return null;
		}
		return $instance;
	}
}

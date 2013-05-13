<?php
namespace System\Auth\Providers;

use System\Auth\BaseAuthProvider;
use System\Auth\Auth;
use System\Applications\IApplication;
using('Libs.Google');
class Google extends BaseAuthProvider {
	private $_client;
	private $_oauth;
	function __construct(IApplication $appOwner) {
		parent::__construct($appOwner, 'google');
	}
	function Initialize() {
		parent::Initialize();
		if (!function_exists('curl_init')) {
			\System::loadExtenstion('php_curl');
			if (!function_exists('curl_init')) {
				$this->setLastError('CURL PHP extension not installed');
				return false;
			}
		}

		// https://code.google.com/apis/console/
		$client = \GoogleAPI::getAuthInstance();

		$auth = \GoogleAPI::getOAuth2Instance();
		//$token = $this->getFromSession('token');

		//$plus = \GoogleAPI::getPlusService();
		if (isset($_GET['code'])) {
			try {
				$client->authenticate();
				$this->setToSession('token', $client->getAccessToken());
				\Response::Redirect(\URLHelper::add(APP_URL, '/auth/external?id=google'));
			} catch (\Exception $e) {
				$this->setState(Auth::ERROR_STATE);
				$this->setLastError($e->getMessage());
			}
			return true;
		}
		$token = $this->getFromSession('token');
		if ($token) {
			$client->setAccessToken($token);
			$this->setState(Auth::NEED_CONFIRM_LOCAL_STATE);
		}
		if ($client->getAccessToken()) {
			$this->setToSession('token', $client->getAccessToken());
		} else {
			$client->authenticate();
		}
		return true;
	}
	function getRemoteUser() {
		$token = \GoogleAPI::getAuthInstance()->getAccessToken();
		if ($token) {
			if (!$this->_remoteUser) {
				$user = \GoogleAPI::getOAuth2Instance()->userinfo->get();
				$retval = new \stdClass();
				$retval->__ori = $user;
				$retval->id = $user['id'];
				$retval->profilelink = $user['link'];
				$retval->name = $user['name'];
				$retval->birth_date = $user['birthday'];
				$retval->email = $user['email'];
				$retval->gender = $user['gender'];
				$retval->first_name = $user['given_name'];
				$retval->last_name = $user['family_name'];
				parent::setRemoteUser($retval);
			}
		}
		return $this->_remoteUser;
	}

	function getLogoutUrl() {
		return \GoogleAPI::getAuthInstance()->createAuthUrl();
	}
}

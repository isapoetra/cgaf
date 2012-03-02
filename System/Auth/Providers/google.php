<?php
namespace System\Auth\Providers;
use System\Session\Session;
use System\Auth\BaseAuthProvider;
use System\Auth\Auth;
using ( 'Libs.Google' );
class Google extends BaseAuthProvider {
	private $_client;
	private $_oauth;
	private $_remoteUser;
	function Initialize() {
		if (! function_exists ( 'curl_init' )) {
			\System::loadExtenstion ( 'php_curl' );
			if (! function_exists ( 'curl_init' )) {
				$this->setLastError('CURL PHP extension not installed');
				return false;
			}
		}
		// https://code.google.com/apis/console/
		$this->_client = $client = \GoogleAPI::getAuthInstance ();
		$this->_oauth = $oauth2 = \GoogleAPI::getOAuth2Instance ( $this->_client );
		if (isset ( $_GET ['code'] )) {
			try {
				$client->authenticate ();
				Session::set ( 'auth.google.token', $this->_client->getAccessToken () );
				$this->setState ( Auth::NEED_CONFIRM_LOCAL_STATE );
			} catch ( \Exception $e ) {
				$this->setLastError ( $e->getMessage () );
				return false;
			}
			return true;
		}
		$token = Session::get ( 'auth.google.token' );
		if ($token) {
			$this->_client->setAccessToken ( $token );
			$this->setState ( Auth::NEED_CONFIRM_LOCAL_STATE );
		} else {
			$this->_client->authenticate ();
		}
		return true;
	}
	function getRemoteUser() {
		if ($this->_client->getAccessToken ()) {
			$user = $this->_oauth->userinfo->get ();
			$retval = new \stdClass ();
			$retval->id = $user ['id'];
			$retval->profilelink = $user ['link'];
			$retval->name = $user ['name'];
			$retval->birth_date = $user ['birthday'];
			$retval->email = $user ['email'];
			$retval->gender = $user ['gender'];
			return $retval;
		}
	}
	function getLogoutUrl() {
		return $this->_client->createAuthUrl ();
	}
}

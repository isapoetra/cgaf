<?php
namespace System\Controllers;
use System\JSON\JSONResult;
use System\API\facebook;
use System\Exceptions\UnimplementedException;
use System\Web\JS\JSUtils;
use System\Auth\Auth;
use System\MVC\Controller;
use System\Auth\OAuth;
use System\Auth\Google;
use System\Exceptions\SystemException;
use System\Auth\OpenId;
use System\Auth\Google\Common;
use System\Session\Session;
use System\JSON;
use System\Auth\Yadis\Yadis;
use System\Auth\OAuth\OAuthRequest;
use Request;
use Response;
use URLHelper;
class AuthController extends Controller {
	public function isAllow($access = "view") {
		$isAuth = $this->getAppOwner ()->isAuthentificated ();
		switch (strtolower ( $access )) {
			case 'external' :
				// damn recursive on facebook
				return $this->getAppOwner ()->getConfig ( 'auth.External.enabled', true );
			case 'index' :
			case 'view' :
				return true;
			case 'login' :
				return $isAuth === false;
			case 'logout' :
				return $isAuth === true;
		}
		return parent::isAllow ( $access );
	}
	function external() {
		$p = Request::get ( 'id' );
		$mode = Request::get ( 'mode' );
		if (\Request::get ( 'popupmode' )) {
			\Request::isAJAXRequest ( true );
			\Response::clearBuffer ();
		}
		$ajax = Request::isAJAXRequest () ? '__ajax=1&' : '';
		if ($this->getAppOwner ()->isAuthentificated ()) {
			if ($ajax) {
				return $this->renderView ( 'external/success' );
			} else {
				\Response::Redirect ( \URLHelper::add ( APP_URL, 'auth/logout/' ) );
			}
		}
		$provider = Auth::getProviderInstance ( $p );
		if (! $provider) {
			throw new SystemException ( "unhandled authentification method" );
		}
		$rurl = \URLHelper::add ( APP_URL, 'auth/external/', $ajax . 'id=' . $p );
		$state = $provider->getState ();
		$params = array (
				'lastError' => $provider->getLastError (),
				'providername' => $p,
				'provider' => $provider,
				'localuser' => $provider->getRealUser (),
				'remoteuser' => $provider->getRemoteUser (),
				'authurl' => $rurl
		);
		if ($mode) {
			switch ($mode) {
				case 'rtl' : // register to local
					if ($this->getAppOwner()->isValidToken()) {

					}
					if (! $params ['localuser'] && $params ['remoteuser']) {
						$u = $this->getController ( 'user' );
						try {
							$valid = $u->registerDirect ( $params ['remoteuser'], $p );
							if (!$valid['result']) {
								$params['message'] = $valid['message'];
								return $this->renderView ( 'external/register', $params );
							}
						} catch ( \Exception $e ) {
							$params ['msg'] = $e->getMessage ();
							\Request::isAJAXRequest ( false );
							return $this->renderView ( 'external/register', $params );
						}
					}
					break;
			}
		}
		if ($params ['localuser'] && ! $this->getAppOwner ()->isAuthentificated () && \Request::get ( '__confirm' )) {
			$ruser = $params ['localuser'];
			$auth = $this->getAppOwner ()->getAuthentificator ();
			if ($auth->authDirect ( $ruser->user_name, $ruser->user_password, $p )) {
				$script = <<< EOT
if (window.opener) {
	window.opener.reload();
	window.close();
}else{
	window.location.reload();
}
EOT;
				return JSUtils::renderJSTag ( $script, false );
			}
		}
		switch ($state) {
			case AUTH::ERROR_STATE :
				return $this->renderView ( 'external/error', $params );
			case Auth::NEED_RETRY_STATE :
				return $this->renderView ( 'external/retry', $params );
				break;
			case Auth::NEED_REMOTEAUTH_STATE :
				$url = $provider->getLoginURL ( $rurl );
				\Response::Redirect ( $url );
				break;
			case Auth::NEED_CONFIRM_LOCAL_STATE :
				return $this->renderView ( 'external/localconfirm', $params );
			default :
				ppd ( 'haloo unhandled state ' . $state );
				break;
		}
	}
	function openid() {
		if (! CGAF_DEBUG) {
			throw new UnimplementedException ();
		}
		$this->addClientAsset ( array (
				'openid.css',
				'openid.js'
		) );
		$vars = array (
				'providers' => OpenId::getSupportedProviders (),
				'msg' => '',
				'error' => '',
				'success' => '',
				'response' => null
		);
		$openid = OpenId::getInstance ();
		$action = \Request::get ( 'action' );
		if ($action) {
			try {
				switch (strtolower ( $action )) {
					case 'verify' :
						return $openid->Verify ();
						break;
					case 'finish' :
						$vars ['response'] = $openid->finish ();
					default :
						break;
				}
			} catch ( \Exception $ex ) {
				$vars ['error'] .= "<p>" . $ex->getMessage () . '</p>';
			}
		}
		return parent::render ( null, $vars );
	}
	function logout() {
		$confirm = Request::get ( '__confirm' );
		if (! $confirm) {
			return $this->render ( 'confirm', array (
					'title' => __ ( 'auth.signout.confirm.title' ),
					'descr' => __ ( 'auth.signout.confirm.descr' )
			) );
		}
		if (strtolower ( $confirm === 'yes' ) || $confirm === '1') {
			$this->getAppOwner ()->LogOut ();
		}
		Response::Redirect ( BASE_URL . '?__t=' . time () );
	}
	function Index() {
		$retval = false;
		$appOwner = $this->getAppOwner ();
		$redirect = null;
		$msg = Request::get ( 'msg' );
		if (! $appOwner->isAuthentificated ()) {
			if ($appOwner->isValidToken ()) {
				$args = Request::gets ();
				$msg = null;
				try {
					$retval = $this->getAppOwner ()->Authenticate ();
				} catch ( \Exception $e ) {
					$msg = $e->getMessage ();
				}
				if (! $msg && ! $retval) {
					$msg = $this->getAppOwner ()->getAuthentificator ()->getLastError ();
				}
				$redir = Request::get ( 'original_url', Request::get ( "redirect", URLHelper::add ( APP_URL, 'auth' ) ) );
				if ($retval) {
					$redir = URLHelper::addParam ( $redir, array (
							'__t' => time ()
					) );
				} else {
					$redir = URLHelper::addParam ( BASE_URL . '/auth/local/', array (
							'__t' => time (),
							'__msg' => $msg
					) );
				}
				if (Request::isJSONRequest ()) {
					if (! $retval) {
						return new JSONResult ( false, array (
								"message" => $msg,
								"__token" => $this->getAppOwner ()->getToken ()
						) );
					} else {
						return new JSONResult ( true, array (
								"_result" => true,
								"_redirect" => $redir
						) );
					}
				} elseif ($redirect) {
					Response::Redirect ( $redir );
				}
			} elseif (Request::get ( '__token' )) {
				$msg = __ ( 'error.invalidtoken', 'Invalid Token' );
				if (Request::isAJAXRequest ()) {
					throw new SystemException ( $msg );
				}
			}
		}
		if ($appOwner->isAuthentificated ()) {
			\Response::Redirect ( BASE_URL . 'user/dashboard/' );
		} else {
			$providers = array ();
			if ($this->getAppOwner ()->getConfig ( 'auth.External.enabled', true )) {
				$providers = $this->getAppOwner ()->getConfig ( 'auth.External.providers', array (
						'google',
						'facebook',
						'oauth'
				) );
			}
		}
		if (Request::get ( '__token', null, true, 'p' )) {
			$this->getAppOwner ()->resetToken ();
		}
		$retval = parent::render ( 'form/login', array (
				'providers' => $providers,
				'msg' => $msg
		), true );
		return $retval;
	}
}
?>

<?php
namespace System\Controllers;
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
use \Request;
use \Response;
use \URLHelper;
class AuthController extends Controller {
	public function isAllow($access = "view") {
		$isAuth = $this->getAppOwner()->isAuthentificated();
		switch (strtolower($access)) {
		case 'external':
		//damn recursive on facebook
			return CGAF_DEBUG;
		case 'index':
		case 'view':
			return true;
		case 'login':
			return $isAuth === false;
		case 'logout':
			return $isAuth === true;
		}
		return parent::isAllow($access);
	}
	function external() {
		$p = Request::get('id');
		if (\Request::get('popupmode')) {
			\Request::isAJAXRequest(true);
			\Response::clearBuffer();
		}

		$ajax = Request::isAJAXRequest() ? '__ajax=1&' : '';
		if ($this->getAppOwner()->isAuthentificated()) {
			if ($ajax) {
				return $this->renderView('external/success');
			} else {
				\Response::Redirect(\URLHelper::add(APP_URL, 'auth/logout/'));
			}
		}

		$provider = Auth::getProviderInstance($p);

		if (!$provider) {
			throw new SystemException("unhandled authentification method");
		}
		$rurl = \URLHelper::add(APP_URL, 'auth/external/', $ajax . 'id=' . $p);
		$state = $provider->getState();
		$params = array(
				'lastError' => $provider->getLastError(),
				'providername' => $p,
				'provider' => $provider,
				'localuser' => $provider->getRealUser(),
				'remoteuser' => $provider->getRemoteUser());

		if ($params['localuser'] && !$this->getAppOwner()->isAuthentificated() && \Request::get('__confirm')) {
			$ruser = $params['localuser'];
			$auth = $this->getAppOwner()->getAuthentificator();
			ppd($auth);
			if ($auth->authDirect($ruser->user_name, $ruser->user_password, Auth::FACEBOOK)) {
				$script = <<< EOT
if (window.opener) {
	window.opener.reload();
	window.close();
}else{
	window.location.reload();
}
EOT;
				return JSUtils::renderJSTag($script, false);
			}
		}
		switch ($state) {
		case AUTH::ERROR_STATE:
			return $this->renderView('external/error', $params);
		case Auth::NEED_RETRY_STATE:
			return $this->renderView('external/retry', $params);
			break;
		case Auth::NEED_REMOTEAUTH_STATE:
			$url = $provider->getLoginURL($rurl);
			\Response::Redirect($url);
			break;
		case Auth::NEED_CONFIRM_LOCAL_STATE:
			return $this->renderView('external/localconfirm', $params);
		default:
			ppd('haloo unhandled state ' . $state);
			break;
		}
	}
	function openid() {
		if (!CGAF_DEBUG) {
			throw new UnimplementedException();
		}
		$this->addClientAsset(array(
						'openid.css',
						'openid.js'));
		$vars = array(
				'providers' => OpenId::getSupportedProviders(),
				'msg' => '',
				'error' => '',
				'success' => '',
				'response' => null);
		$openid = OpenId::getInstance();
		if ($action = Request::get('action')) {
			try {
				switch (strtolower($action)) {
				case 'verify':
					return $openid->Verify();
					break;
				case 'finish':
					$vars['response'] = $openid->finish();
				default:
					break;
				}
			} catch (Exception $ex) {
				$vars['error'] .= "<p>" . $ex->getMessage() . '</p>';
			}
		}
		return parent::render(null, $vars);
	}
	function google() {
		if (!CGAF_DEBUG) {
			throw new UnimplementedException();
		}
		$google = Google::getInstance();
		$openid_params = $google->getOpenId_Params();
		$request_token = Request::get('openid_ext2_request_token');
		$data = array();
		$sess_redir = 'auth.google.redirect_to';
		$realm = $openid_params['openid.realm'];
		$return = $openid_params['openid.return_to'];
		$consumerKey = $google->getConsumerKey();
		//Session::remove($sess_redir);
		$debugmsg = array();
		if (Request::get('popup') && !Session::get($sess_redir)) {
			$redirect = URLHelper::add(BASE_URL, 'auth/google/', Request::getIgnore('popup'));
			Session::set($sess_redir, $redirect);
			echo '<script type = "text/javascript">window.close();</script>';
			exit;
		} else if (Session::get($sess_redir)) {
			$redirect = Session::get($sess_redir);
			Session::remove($sess_redir);
			Response::Redirect($redirect);
		}
		if (Request::get('openid_mode')) {
			$consumer = $google->getConsumer();
		}
		$request_token = Request::get('openid_ext2_request_token', null, false);
		if ($request_token) {
			using('Libs.Zend');
			$data = array();
			$httpClient = new Zend\GData\HttpClient();
			$access_token = $google->getAccessToken($request_token);
			// Query the Documents API ===================================================
			$feedUri = 'http://docs.google.com/feeds/documents/private/full';
			$params = array(
					'max-results' => 50,
					'strict' => 'true');
			$req = OAuthRequest::from_consumer_and_token($consumer, $access_token, 'GET', $feedUri, $params);
			$req->sign_request($sig_method, $consumer, $access_token);
			// Note: the Authorization header changes with each request
			$httpClient->setHeaders($req->to_header());
			$docsService = new Zend_Gdata_Docs($httpClient);
			$query = $feedUri . '?' . implode_assoc('=', '&', $params);
			$feed = $docsService->getDocumentListFeed($query);
			$data['docs']['html'] = listEntries($feed);
			$data['docs']['xml'] = $feed->saveXML();
			// ===========================================================================
			// Query the Spreadsheets API ================================================
			$feedUri = 'http://spreadsheets.google.com/feeds/spreadsheets/private/full';
			$params = array(
					'max-results' => 50);
			$req = OAuthRequest::from_consumer_and_token($consumer, $access_token, 'GET', $feedUri, $params);
			$req->sign_request($sig_method, $consumer, $access_token);
			// Note: the Authorization header changes with each request
			$httpClient->setHeaders($req->to_header());
			$spreadsheetsService = new Zend_Gdata_Spreadsheets($httpClient);
			$query = $feedUri . '?' . implode_assoc('=', '&', $params);
			$feed = $spreadsheetsService->getSpreadsheetFeed($query);
			$data['spreadsheets']['html'] = listEntries($feed);
			$data['spreadsheets']['xml'] = $feed->saveXML();
			// ===========================================================================
			// Query Google's Portable Contacts API ======================================
			$feedUri = 'http://www-opensocial.googleusercontent.com/api/people/@me/@all';
			$req = OAuthRequest::from_consumer_and_token($consumer, $access_token, 'GET', $feedUri, NULL);
			$req->sign_request($sig_method, $consumer, $access_token);
			// Portable Contacts isn't GData, but we can use send_signed_request() from
			// common.inc.php to make an authenticated request.
			$data['poco'] = send_signed_request($req->get_normalized_http_method(), $feedUri, $req->to_header(), NULL, false);
			// ===========================================================================
		}
		switch (Request::get('openid_mode')) {
		case 'checkid_setup':
		case 'checkid_immediate':
			$identifier = Request::get('openid_identifier');
			if ($identifier) {
				$fetcher = Yadis::getHTTPFetcher();
				list($normalized_identifier, $endpoints) = OpenId::discover($identifier, $fetcher);
				if (!$endpoints) {
					$debugmsg[] = 'No OpenID endpoint found.';
				}
				$uri = '';
				foreach ($openid_params as $key => $param) {
					$uri .= $key . '=' . urlencode($param) . '&';
				}
				header('Location: ' . $endpoints[0]->server_url . '?' . rtrim($uri, '&'));
			} else {
				$debugmsg[] = 'No OpenID endpoint found.';
			}
			break;
		case 'cancel':
			$debugmsg[] = 'Sign-in was cancelled.';
			break;
		case 'associate':
		// TODO
			break;
		}
		return parent::render(null,
				array(
						'openid_ext' => json_encode($google->getOpenidExt()),
						'redirect' => Session::get($sess_redir, BASE_URL . 'auth/google'),
						'realm' => $realm,
						'return' => $return,
						'request_token' => $request_token,
						'debugmsg' => $debugmsg,
						'data' => $data));
	}
	function logout() {
		$confirm = Request::get('__confirm');
		if (!$confirm) {
			return $this->render('confirm', array(
							'title' => __('auth.signout.confirm.title'),
							'descr' => __('auth.signout.confirm.descr')));
		}
		if (strtolower($confirm === 'yes') || $confirm === '1') {
			$this->getAppOwner()->LogOut();
		}
		Response::Redirect(BASE_URL . '?__t=' . time());
	}
	function Index() {
		$retval = false;
		$appOwner = $this->getAppOwner();
		$redirect = null;
		$msg = Request::get('msg');
		if (!$appOwner->isAuthentificated()) {
			if ($appOwner->isValidToken()) {
				$args = Request::gets();
				$msg = null;
				try {
					$retval = $this->getAppOwner()->Authenticate();
				} catch (Exception $e) {
					$msg = $e->getMessage();
				}
				if (!$msg && !$retval) {
					$msg = $this->getAppOwner()->getAuthentificator()->getLastError();
				}
				$redir = Request::get('original_url', Request::get("redirect", URLHelper::add(APP_URL, 'auth')));
				if ($retval) {
					$redir = URLHelper::addParam($redir, array(
							'__t' => time()));
				} else {
					$redir = URLHelper::addParam(BASE_URL . '/auth/local/', array(
							'__t' => time(),
							'__msg' => $msg));
				}
				if (Request::isJSONRequest()) {
					if (!$retval) {
						return new JSONResult(false, array(
								"message" => $msg,
								"__token" => $this->getAppOwner()->getToken()));
					} else {
						return new JSONResult(true, array(
								"_result" => true,
								"_redirect" => $redir));
					}
				} elseif ($redirect) {
					Response::Redirect($redir);
				}
			} elseif (Request::get('__token')) {
				$msg = __('error.invalidtoken', 'Invalid Token');
				if (Request::isAJAXRequest()) {
					throw new SystemException($msg);
				}
			}
		}
		if ($appOwner->isAuthentificated()) {
			\Response::Redirect(BASE_URL . 'user/dashboard/');
		} else {
			$providers = array();
			if ($this->getAppOwner()->getConfig('auth.External.enabled', CGAF_DEBUG)) {
				$providers = $this->getAppOwner()->getConfig('auth.External.providers', array(
								'google',
								'facebook',
								'oauth'));
			}
		}
		if (Request::get('__token', null, true, 'p')) {
			$this->getAppOwner()->resetToken();
		}
		$retval = parent::render('form/login', array(
				'providers' => $providers,
				'msg' => $msg), true);
		return $retval;
	}
}
?>

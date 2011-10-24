<?php
namespace System\Auth\Providers;
use System\Auth\RemoteUser;
use System\Auth\Auth;
use System\Auth\BaseAuthProvider;
using('Libs.facebook');
class Facebook extends BaseAuthProvider {
	private $_fb;
	private $_fbUser = 0;
	private $_fbUserInfo = null;
	function __construct(\IApplication $appOwner) {
		$this->_fb = \FBUtils::getInstance();
		parent::__construct($appOwner);
	}
	protected function Initialize() {
		$fb = $this->_fb;
		$this->setState(Auth::NEED_REMOTEAUTH_STATE);
		$this->_fbUser = $fb->getUser();
		if ($this->_fbUser) {
			try {
				// Proceed knowing you have a logged in user who's authenticated.
				$rc = $fb->api('/me');
				$ui = new RemoteUser();
				$ui->id = $rc['id'];
				$ui->profilelink = $rc['link'];
				$ui->name = $rc['name'];
				$this->_fbUserInfo = $ui;
				$uc = $this->getAppOwner()->getController('user');
				$this->_realUser = $uc->getUserRegisterExternal(Auth::FACEBOOK, $rc['id']);
				$this->setState(Auth::NEED_CONFIRM_LOCAL_STATE);
			} catch (\FacebookApiException $e) {
				$this->_lastError = 'Error While accessing facebook service. ' . $e->getMessage();
				$this->setState(Auth::NEED_RETRY_STATE);
			}
		}
		$this->_isAuthentificated = $this->_fbUser && $this->_realUser;
	}
	function getRemoteUser() {
		return $this->_fbUserInfo;
	}
	function getLogoutURL() {
		return $this->_fb->getLogoutURL();
	}
	function getLoginURL($baseUrl) {
		return $this->_fb
				->getLoginURL(
						array(
								'display' => 'popup',
								'redirect_uri' => $baseUrl,
								'next' => \URLHelper::addParam($baseUrl, 'loginsucc=1'),
								'cancel_url' => \URLHelper::addParam($baseUrl, 'cancel=1'),
								'req_perms' => 'email,user_birthday'));
	}
}

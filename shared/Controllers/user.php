<?php
namespace System\Controllers;
use System\Auth\Auth;
use System\DB\DBQuery;
use \AppManager;
use System\MVC\ViewModel;
use System\MVC\Controller;
use System\ACL\ACLHelper;
use System\Web\Utils\HTMLUtils;
use \Request;
use \Convert;
use \Response;
use \CGAF;
class UserController extends Controller {
	public function isAllow($access = "view") {
		$isAuth = $this->getAppOwner()->isAuthentificated();
		switch (strtolower($access)) {
		case 'fbregister':
		case ACLHelper::ACCESS_VIEW:
		case "activate":
		case "forgetpassword":
		case "checkuserexist":
		case "resetpassword":
		case 'login':
		case "logout":
		case "view":
		case 'selectopenid':
		case 'fbRegister':
		case "index":
			return true;
			break;
		case "register":
			return $isAuth === false;
			break;
		case 'dashboard':
		case "profile":
			return $isAuth && CGAF_DEBUG;
		case "updateprofile":
		case 'dashboard':
		case "action":
			return $isAuth;
		case 'del':
			$access = 'delete';
			break;
		}
		return parent::isAllow($access);
	}
	function Initialize() {
		parent::Initialize();
		$this->setModel('user');
		return true;
	}
	function selectopenid() {
		$this->addClientAsset('openid.css');
		$this->addClientAsset('openid.js');
		$providers = \System\Auth\OpenId::getSupportedProvider();
		return parent::render(null, array(
				'providers' => $providers,
				true,
				true));
	}
	function quickasign() {
		if ($this->getAppOwner()->isValidToken()) {
			$acl = $this->getACL();
			$id = ACLHelper::isAllowUID(Request::get('id'), 'view', true);
			if ($id !== null) {
				$ui = $this->getModel('user')->load($id);
				$acl->assignRole($ui->user_id, Request::get('value'));
			}
			Response::Redirect(BASE_URL . '/user/detail/?id=' . $id);
		}
		//ppd(Request::gets());
		$rm = $this->getModel('roles')->loadSelect();
		return parent::render(array(
				'_a' => 'shared/updatestatus'), array(
				'lookup' => $rm,
				'selected' => ACLHelper::isAllowUID(Request::get('id'))));
	}
	private function generateActivationKey() {
		return Utils::generateActivationKey();
	}
	private function doRegister() {
		$type = Request::get('type');
		$app = $this->getAppOwner();
		switch ($type) {
		case 'partner':
		case 'member':
			return new JSONResult(true, sprintf(__('user.register.result_step1', '%s'), ucfirst($type)));
			break;
		default:
			$uname = Request::get("user_name");
			$msg = null;
			$valid = true;
			$m = $this->getModel("user");
			$o = $m->clear()->where("user_name=" . $m->quote($uname))->loadObject();
			$birth_date = Request::get("birth_date");
			$date = \DateUtils::split($birth_date, __('client.dateFormat'));
			if ($o && $o->user_id != null) {
				$msg = "username already registered";
			}
			if ($app->getConfig('user.register.profile', true)) {
				if (!$birth_date || count($date) != 3) {
					$msg = "Invalid Birth Date";
				} elseif (!DateUtils::isValidDate($date[0], $date[1], $date[2])) {
					$msg = "Invalid Birth Date";
				} else {
					$dt = new CDate();
					$date = explode("/", $birth_date);
					$dt->setDayMonthYear($date[0], $date[1], $date[2]);
					$today = new CDate();
					$old = abs(round($dt->dateDiff($today) / 365));
					$yd = $this->getAppOwner()->getConfig("user.minage", 15);
					if ($old < $yd) {
						$msg = sprintf(__("user.register.error.toyoung", "You are to young to register (%s), minimum old is %s years"), $old, $yd);
					}
				}
			}
			if (!$msg) {
				$m->bind(Request::gets());
				$person = $this->getModel("person");
				$person->bind(Request::gets());
				if ($birth_date) {
					$dt = DateUtils::toDate($birth_date);
					$person->birth_date = $dt->format(FMT_DATETIME_MYSQL);
				}
				$person->email = $m->user_name;
				$person->first_name = $person->first_name ? $person->first_name : $m->user_name;
				$id = $person->store()->getLastInsertId();
				$m->clear();
				$m->bind(Request::gets());
				$m->person_id = $id;
				$m->user_status = 0;
				$m->user_state = 0;
				$m->user_password = $this->getAppOwner()->getAuthentificator()->encryptPassword($m->user_password);
				$m->activation_key = $activationKey = $this->generateActivationKey();
				$m->store();
				WebUtils::sendMail($m, "registration");
				return array(
						"_result" => true,
						"_redirect" => BASE_URL . "/?_message=check your email");
			} else {
				if (Request::isJSONRequest()) {
					return array(
							"_result" => false,
							"message" => $msg);
				} else {
					$o = new stdClass();
					Convert::toObject(Request::gets('p'), $o);
					return $app->renderView('register', null, array(
									'msg' => $msg,
									'row' => $o));
				}
			}
			break;
		}
	}
	public function register() {
		$app = $this->getAppOwner();
		if ($this->getAppOwner()->isValidToken()) {
			return $this->doRegister();
		} else {
			$type = Request::get('type');
			$nroute = null;
			$acl = $this->getACL();
			switch ($type) {
			case 'member':
				if ($acl->isinrole(ACLHelper::MEMBERS_GROUP)) {
					throw new SystemException('error.alreadymember');
				}
			case 'partner':
				if (!$this->getAppOwner()->isAuthentificated()) {
					throw new AccessDeniedException();
				}
				if ($type === 'partner' && $acl->isinrole(ACLHelper::PARTNERS_GROUP)) {
					throw new SystemException('error.alreadypartner');
				}
				$nroute['_a'] = 'register/' . $type;
			}
			return parent::render($nroute, array(
					'type' => $type));
		}
	}
	function forgetpassword() {
		$login = Request::get("login");
		if (!$login) {
			throw new SystemException("Invalid Login");
		}
		if ($this->getAppOwner()->isValidToken()) {
			$m = $this->getModel("user");
			$o = $m->clear()->where("user_name=" . $m->quote($login))->loadObject();
			if ($o) {
				if ($o->user_state == 0) {
					throw new SystemException('user.notactive');
				}
				$generated = Utils::generatePassword();
				$o->activation_key = $this->generateActivationKey();
				$o->user_password = $this->getAppOwner()->getAuthentificator()->encryptpassword($generated);
				$m->bind($o);
				if ($m->store()) {
					$o = $m->getUserByEmail($login);
					$o->generatedPassword = $generated;
					MailHelper::sendMail($o, "forgetpassword", 'user.passwordreminder.emailtitle');
					return new JSONResult(true, 'user.passwordreminder.ok');
				}
			} else {
				throw new SystemException('user.notfound');
			}
		}
	}
	function add() {
		return parent::aed();
	}
	public function store() {
		$uid = ACLHelper::isAllowUID(Request::get('user_id'));
		if ($uid) {
			return parent::store();
		}
	}
	public function updateProfile() {
		$uid = ACLHelper::isAllowUID(Request::get('id'));
		if ($uid) {
			$m = $this->getModel();
			$o = $m->clear()->where('user_id=' . (int) $uid)->loadObject();
			$m->load($uid)->bind(Request::gets());
			if ($m->user_password != $o->user_password) {
				if (!$m->user_password !== Request::get('confirm_password')) {
					throw new CGAFException('error.invalidconfirmpassword', 'Invalid Password confirmation');
				}
				$m->user_password = $this->getAppOwner()->getAuthentificator()->encryptPassword($m->user_password);
			}
			if ($o = $m->store()) {
				if (!$m->person_id) {
					$m->person_id = $m->user_id;
					$m->store();
				}
				$pm = $this->getModel('person');
				if ($pm) {
					$po = $pm->load($m->person_id);
					if ($po) {
						$o = $po->bind(Request::gets());
						$o->store();
					} else {
						$pm->bind(Request::gets());
						$pm->person_id = $m->person_id;
						$pm->store();
					}
				}
			}
		} else {
			throw new AccessDeniedException();
		}
		if (!Request::isJSONRequest()) {
			return $this->profile(Request::get("id"));
		} else {
			return Response::JSON(1, 'Data Saved');
		}
	}
	function changepassword($uid = null) {
		$uid = $uid != null ? $uid : Request::get("id");
		$uid = ACLHelper::isAllowUID($uid);
		if ($uid == -1) {
			throw new SystemException(__("error.changepassguest", "Cannot change guest password"));
		}
		$adminmode = $this->getACL()->isAdmin();
		$row = $this->getModel()->clear()->load($uid);
		if (Request::isJSONRequest()) {
			$newpass = Request::get('password');
			$confirm = Request::get('confirmpassword');
			if ($newpass === $confirm) {
				if ($newpass !== $row->user_password) {
					$this->getModel()->bind($row)->user_password = $this->getAppOwner()->getAuthentificator()->encryptPassword(Request::get('password'));
					$this->getModel()->store();
					return new JSONResult(1, 'user.passwordchanged');
				}
			} else {
				return new JSONResult(false, 'user.passwordnomatch');
			}
		}
		return parent::render(null, array(
				"adminmode" => $adminmode,
				"row" => $row));
	}
	function action() {
		$id = ACLHelper::isAllowUID(Request::get('id'));
		$arrAction = array(
				array(
						'key' => 'password',
						'title' => 'Change Password',
						'url' => BASE_URL . '/user/changepassword/?id=' . $id,
						'attr' => array(
								'rel' => '#overlay')),
				array(
						'key' => 'profile',
						'title' => 'Change Profile',
						'url' => BASE_URL . '/user/profile/?id=' . $id));
		if ($this->getACL()->isAdmin()) {
			$arrAction[] = array(
					'key' => 'assign_role',
					'title' => 'Assign Role',
					'url' => BASE_URL . 'user/quickasign/?id=' . $id,
					'attr' => array(
							'rel' => '#overlay'));
			/*$arrAction [] = array (
			 'key' => 'sendmail',
			'title' => 'Send Email',
			'link' => '/user/sendmail/?id=' . $id . '&overlay=true'
			);*/
		}
		return HTMLUtils::renderLinks($arrAction, array(
				'class' => 'user-action'));
		/*return $this->renderMenu ( 'user-action', 'user-action', true, array (
		 'id' => $id
		) );*/
	}
	function manage($vars = null, $newroute = null, $return = false) {
		$vars = $vars ? $vars : array();
		$row = $this->getModel()->reset();
		$vars["row"] = $row;
		$vars['links'] = array(
				'<a href="' . BASE_URL . '/acl/manage/?_a=roles">Roles</a>');
		$vars['autogenerategridcolumn'] = false;
		$vars["columns"] = array(
				"__action" => array(
						"<a href=\"" . BASE_URL . "/user/action/?id=#user_id#\" rel=\"__overlay\">Action</a>"),
				'user_name' => '#user_name#',
				'state' => '<a href="' . BASE_URL . '/user/updatestate/?id=#user_id#" rel="__overlay">#state#</a>',
				'status' => '<a href="' . BASE_URL . '/user/updatestatus/?id=#user_id#" rel="__overlay">#status#</a>',
				'registerDate',
				'session_id' => array(
						'width' => '150',
						'title' => "Session Id",
						'eval' => '"#session_id#"? "<a href=\"' . BASE_URL . '/user/kill/?sid=#session_id#\" rel=\"__overlay\">kill</a>":""'));
		return parent::manage($vars, $newroute, $return);
	}
	function detail($args = null, $return = null) {
		return $this->profile(null, array(
						'_detail' => 1));
	}
	function profile($uid = null, $vars = null) {
		if (!Request::isAJAXRequest()) {
			$this->getAppOwner()->getJSEngine()->loadUI();
		}
		$vars = $vars ? $vars : array();
		$current = null;
		$userinfo = null;
		$personInfo = null;
		if ($this->getAppOwner()->isAuthentificated()) {
			$data = $this->getAppOwner()->getAuthInfo();
			$identify = $data->getIdentify();
			$m = $this->getModel("user");
			$current = $m->loadByIdentify($identify);
			$uid = $uid !== null && !is_array($uid) ? $uid : Request::get('id', $current ? $current->user_id : -1);
			$userinfo = $m->clear()->where("user_id=" . $uid)->loadObject();
			$acl = $this->getAppOwner()->getACL();
			$person = $this->getAppOwner()->getModel("person");
			$personInfo = $person->getPersonByUser($uid);
			if (!$personInfo) {
				$personInfo = $this->getModel('person');
			}
			$views = array(
					'profile' => 'user.profile.title',
					'roles' => 'user.roles.title');
		} else {
			$uid = -1;
			$person = null;
		}
		/*if ($acl->isPartner()) {
		 $views ["product"] = "Product";
		}*/
		$vars = array_merge($vars, array(
				'user_id' => $uid,
				"data" => $userinfo,
				"personInfo" => $personInfo,
				"person" => $person));
		return parent::render(array(
				"_a" => "profile"), $vars);
	}
	public function handleAccessDenied($action) {
		switch (strtolower($action)) {
		case 'pic':
			$img = $this->getAppOwner()->getAsset('user-notallow.png');
			using('System.Media.stream.image');
			$stream = new MediaStream($img);
			$stream->setDisposition(false);
			$stream->stream();
			CGAF::doExit();
		}
		return parent::handleAccessDenied($action);
	}
	function pic() {
	}
	function dashboard() {
		return parent::render(__FUNCTION__);
	}
	function del($id = null) {
		return $this->delete($id);
	}
	function delete($id = null) {
		if (is_array($id) && !count($id)) {
			$id = null;
		}
		$id = $id !== null ? $id : Request::get("id");
		$o = $this->getModel()->where("user_id=" . (int) $id)->loadObject();
		if (!$o) {
			throw new SystemException("error.usernotfound");
		}
		if ($o->user_id == -1 || $o->user_status == 999) {
			throw new SystemException(__("error.cannotdeleteinternaluser", "Deleting internal user is not allowed"));
		}
		$code = 0;
		$msg = '';
		switch ((int) $o->user_status) {
		case -1:
		case 0:
			$this->getModel()->clear()->bind($o)->delete();
			break;
		default:
			throw new SystemException("Sorry, this feature not avaiable yet, check for child delete");
		}
		return new TJSONResponse($code, $msg);
	}
	function updatestate() {
		if (!$this->getACL()->isAdmin()) {
			throw new AccessDeniedException();
		}
		$user = $this->getModel('user');
		if ($this->getAppOwner()->isValidToken()) {
			$user->load((int) Request::get('id'));
			$user->user_state = (int) Request::get('value');
			if ($user->store()) {
				return new JSONResult(true, 'status.updated');
			}
			return new JSONResult(false, $user->getLastError());
		} else {
			$user->load((int) Request::get('id'));
			$q = new DBQuery();
			$q = new ViewModel(CGAF::getDBConnection(), 'vw_user_state');
			$vars = array(
					'selected' => (int) $user->user_state,
					'lookup' => $q->loadObjects());
			return parent::render(array(
					'_a' => 'shared/updatestatus'), $vars);
		}
	}
	function updateStatus() {
		if (!$this->getACL()->isAdmin()) {
			throw new AccessDeniedException();
		}
		if ($this->getAppOwner()->isValidToken()) {
			$o = $this->getModel()->load(Request::get('id'));
			if ($o) {
				$o->user_status = Request::get('value');
				if ($o->user_status == null) {
					$o->user_status = 0;
				}
				if ($o->user_status == 999) {
					return new JSONResult(false, 'user.internalfailed');
				}
				if ($this->getModel()->bind($o)->store()) {
					return new JSONResult(true, 'user.statusupdated');
				}
			} else {
				return new JSONResult(false, 'user.statusupdatefailed');
			}
		} else {
			$user = $this->getModel()->load(Request::get('id'));
			$q = new ViewModel(CGAF::getDBConnection(), 'vw_user_status');
			$vars = array(
					'user' => $user,
					'lookup' => $q->loadObjects());
			//ppd($q->lastSQL());
			return parent::render(null, $vars);
		}
	}
	function kill() {
		$sid = Request::get('sid');
		return $this->getAppOwner()->removeSession($sid);
	}
	function friend() {
		$rows = $this->getModel('friend')->loadFriend();
		return parent::render(null, array(
				'rows' => $rows));
	}
	public function getOnlineInfo() {
		$q = new DBQuery();
		$q->select('role_name name,count(*) count');
		$q->addTable('session', 's');
		$q->join('users', 'u', 's.user_id=u.user_id');
		$q->join('user_roles', 'ur', 'ur.user_id=u.user_id');
		$q->join('roles', 'r', 'ur.role_id=r.role_id');
		$q->groupBy('role_name');
		return $q->loadObjects();
	}
	function getPublicProfile($uid) {
		$q = new DBQuery(\CGAF::getDBConnection());
		$q->select('u.user_id,concat(p.first_name,\' \',p.last_name) fullname');
		$q->addTable('users', 'u', true);
		$q->join('user_person', 'up', 'u.user_id=up.person_id');
		$q->join('person_app', 'pa', 'u.person_id=pa.person_id');
		$q->join('persons', 'p', 'p.person_id=u.person_id');
		$q->where('u.user_id=' . $uid);
		$q->where('pa.app_id=' . $q->quote($this->getAppOwner()->getAppId()));
		$retval = $q->loadObject();
		if ($retval) {
			$retval->user_image = null;
		}
		return $retval;
	}
	function getUserRegisterExternal($mode, $id) {
		$m = $this->getModel('user');
		$m->setAlias('u');
		$m->clear();
		$m->clear('field');
		$m->select('u.*');
		$m->join('user_external', 'ext', 'u.user_id=ext.userid');
		$m->where('extid=' . $m->quote($id));
		$m->where('exttype=' . $m->quote($mode));
		return $m->loadObject();
	}
	function fbRegister() {
		using('Libs.facebook');
		$fb = \FBUtils::getInstance();
		$appOwner = $this->getAppOwner();
		$user_profile = \FBUtils::getUserProfile();
		$user = $fb->getUser();
		if (!$user) {
			Response::redirectToLogin('unable to get facebook user Information');
		}
		$ext = $this->getModel('userexternal');
		$ext->where('exttype=' . $ext->quote(Auth::FACEBOOK));
		$ext->where('extid=' . $ext->quote($user_profile['id']));
		$pass = $this->getAppOwner()->getAuthentificator()->generateRandomPassword();
		$o = $ext->loadObject();
		if (!$o) {
			$u = $this->getModel('user');
			$u->insert('user_name', $user_profile['username']);
			$u->insert('user_state', '1');
			$u->insert('user_password', $pass);
			$r = $u->exec();
			if ($r) {
				$id = $r->getLastInsertId();
				$ext->clear();
				$ext->insert('exttype', Auth::FACEBOOK);
				$ext->insert('extid', $user_profile['id']);
				$ext->insert('userid', $id);
				if ($ext->exec()) {
					$ext->clear();
					$ext->where('exttype=' . $ext->quote('facebook'));
					$ext->where('extid=' . $ext->quote($user_profile['id']));
					$o = $ext->loadObject();
				}
			}
		} else {
			if (\Request::get('authmode')) {
				if (!$this->getAppOwner()->isAuthentificated()) {
					$user = $this->getUserRegisterExternal(Auth::FACEBOOK, $user_profile['id']);
					$auth = $this->getAppOwner()->getAuthentificator();
					if ($auth->authenticateDirect($user->user_name, $user->password)) {
						$script = <<< EOT
if (window.opener) {
	window.opener.reload();
	window.close();
}else{
	window.location.reload();
}
EOT;
						$appOwner->addDirectClientScript($script);
					}
				} else {
					\Response::redirect();
				}
			}
			return true;
		}
		if (!$appOwner->isValidToken()) {
			return $appOwner->renderView('fb/register', null, array(
							'fb' => $fb,
							'user_profile' => $user_profile), 'auth');
		}
		return $this->doRegister();
	}
	function Index() {
		return parent::render();
	}
}

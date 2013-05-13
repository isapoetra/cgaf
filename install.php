<?php if (!defined('CGAF')) {
	include 'cgafinit.php';
}
if (CGAF::isInstalled()) {
	Response::Redirect('index.php');
	exit (0);
}
use System\DB\DBQuery;
use System\Applications\WebApplication;
use System\Session\Session;
use System\DB\DB;
use System\ACL\ACLHelper;

class Install extends WebApplication {
	private $_postError = array();
	private $_steps = array(
			array(
					'title' => 'File/Folder Permissions'
			),
			array(
					'title' => 'Database',
					'defaults' => array(
							'db_type' => 'mysql',
							'db_host' => 'localhost',
							'db_user' => 'root',
							'db_password' => 'sample',
							'db_database' => 'cgaf',
							'db_table_prefix' => ''
					)
			),
			array(
					'title' => 'Admin User',
					'defaults' => array(
							'user_name' => 'admin',
							'user_password' => ''
					)
			),
			array(
					'title' => 'Access Controll',
					'defaults' => array(
							'acl_public' => true
					)
			),
			array(
					'title' => 'Confirm'
			),
			array(
					'title' => 'Finish'
			)
	);

	function __construct() {
		parent::__construct(CGAF_APP_PATH . DS . 'installer', 'install');
	}

	function getACL() {
		return null;
	}

	function isAllow($id, $group, $access = 'view') {
		return \CGAF::isInstalled() === false;
	}

	function getAppName() {
		return 'CGAF Installer';
	}

	function getAppInfo() {
		if (!$this->_appInfo) {
			$info = new \stdClass ();
			$info->app_id = \CGAF::APP_ID;
			$info->app_descr
			= 'CGAF Installer';
			$info->app_name = 'CGAFInstaller';
			$info->app_id =
			'CGAF Installer';
			$info->app_version = CGAF_VERSION;
			$this->_appInfo =
			$info;
		}
		return $this->_appInfo;
	}

	private function isValidStep($step, $defaults) {
		$post = Session::get('install.postedvalues');
		$vstep = isset ($post
				['step_' . $step]) ? $post ['step_' . $step] : array();
		$vstep =
		\Utils::arrayMerge($defaults, $vstep);
		// $vstep = $defaults;
		switch (( int )$step) {
			case 0:
				$rowner = \System::getCurrentUser();
				$dirs = array(
						array(
								\CGAF::getConfig('errors.error_log', \CGAF::getInternalStorage('log', false, true, 0770) . DS),
								'0770',
								$rowner['username'],
								$rowner['groups'],
								'Log Path'
						),
						array(
								CGAF_PATH . 'config.php',
								'0770',
								$rowner['username'],
								$rowner['groups'],
								'Session Path'
						),
						array(
								Session::getInstance()->getConfig('save_path'),
								'0770',
								$rowner['username'],
								$rowner['groups'],
								'Session Path'
						),
						array(
								\CGAF::getInternalStorage('.cache', false),
								'0770',
								$rowner['username'],
								$rowner['groups'],
								'Internal Cache Path'
						),
						array(
								\CGAF::getInternalStorage('persons', false),
								'0770',
								$rowner['username'],
								$rowner['groups'],
								'Internal Cache Path'
						)
				);
				$this->assign('dirs', $dirs);
				foreach ($dirs as $d) {
					$p = new FileInfo($d[0]);
					if ($p->permEqual($d[1],false)) {
						$this->_postError['__common'] = isset($this->_postError['__common']) ? $this->_postError['__common'] : '';
						if ($this->isValidToken()) {
							\Utils::changeFileMode($d[0], $d[1], true);
							$this->_postError['__common'] .= 'unable to change permission please run <i>sudo chmod ' . $d[1] . ' ' . $d[0] . '</i><br/>';
						}


					}
				}
				break;
			case 1 :
				if (!isset ($vstep ['db_type']) || empty ($vstep ['db_type'])) {
					$this->_postError ['db_type'] = 'db.type cannot empty';
				}
				if (!isset ($vstep ['db_host']) || empty ($vstep ['db_host'])) {
					$vstep ['db_host'] = 'localhost';
				}
				if (!isset ($vstep ['db_user']) || empty ($vstep ['db_user'])) {
					$vstep ['db_user'] = 'root';
				}
				if (!isset ($vstep ['db_database']) || empty ($vstep['db_database'])) {
					$vstep ['db_database'] = 'cgaf';
				}
				try {
					$con = DB::connect(array(
							'host' => $vstep ['db_host'],
							'type' => $vstep ['db_type'],
							'database' => $vstep ['db_database'],
							'username' => $vstep['db_user'],
							'password' => $vstep ['db_password'],
							'table_prefix ' => $vstep ['db_table_prefix']
					));
				} catch (\Exception $e) {
					$this->_postError ['__common'] = $e->getMessage();
				}
				break;
			case 2 :
				if (!isset ($vstep ['user_name']) || empty ($vstep ['user_name'])) {
					$vstep ['user_name'] = 'admin';
				}
				if (!isset ($vstep ['user_password'])) {
					$vstep ['user_password'] = '';
				}
				if (empty ($vstep ['user_password'])) {
					$this->_postError ['user_password'] = 'Password cannot empty';
				}
				break;
			case 3 :
			default :
				break;
		}
		$posted = Session::get('install,postedvalues', array());
		$posted['step_' . $step] = $vstep;
		return count($this->_postError) === 0;
	}

	function Run() {
		$steps = $this->_steps;

		$this->assign('steps', $steps);
		$step = ( int )(isset ($_REQUEST ['step']) ? $_REQUEST ['step'] : 0);
		if ($_POST) {
			$cpost = $_POST;
			\UTIls::arrayRemove($cpost, array(
					'__token',
					'nstep',
					'step',
					'next'
			));
			$npost = Session::get('install.postedvalues', array());
			$npost['step_' . $step] = $cpost;
			Session::set('install.postedvalues', $npost
			);
		}

		$isvalid = true;
		for ($i = 0; $i <= $step; $i++) {
			if (!$this->isValidStep($i, isset ($steps [$i] ['defaults']) ? $steps
					[$i] ['defaults'] : array())
			) {
				$isvalid = false;
				$step = $i;
				break;
			}
		}
		if ($isvalid && isset ($_POST ['nstep'])) {
			$step = ( int )$_POST ['nstep'];
		}
		$installConfirm = false;
		if ($step === count($steps) - 1) {
			if (!isset ($_POST ['__confirm'])) {
				$this->_postError [] = 'Please Confirm installation';
				$step = count(
						$steps) - 2;
			} else {
				$installConfirm = true;
			}
		}
		if ($step >= count($steps) - 1) {
			$step = count($steps) - 1;
		} elseif ($step < 0) {
			$step = 0;
		}
		$posted = Session::get('install.postedvalues', array());
		$posted['step_' . $step] = isset ($posted ['step_' . $step]) ?
		$posted ['step_' . $step] :
		(isset ($steps [$step] ['defaults']) ? $steps [$step]	['defaults'] : array());
		$this->assign('cstep', $step);
		$this->assign('nstep', $step + 1);
		$this->assign('posterror', $this->_postError);
		$this->assign('postvalues', $posted ['step_' . $step]);
		Session::set('install.step', $step);
		$installlog = array();

		if ($installConfirm && !\CGAF::isInstalled()) {
			$installlog [] = 'Setting up CGAF Configuration';
			$gconf = CGAF::getConfiguration();
			foreach ($steps as $k => $step) {
				$spost = isset ($posted ['step_' . $k]) ? $posted ['step_' . $k] : array();
				if ($spost) {
					switch ($k) {
						case 1 :
							$db = array();
							foreach ($spost as $kk => $vv) {
								$db [substr($kk, 3)] = $vv;
							}
							$gconf->setConfig('db', $db);
							break;
					}
				}
			}
			
			\CGAF::reloadConfig();
			$con = DB::Connect($gconf->getConfigs('db'));
			$installlog [] = 'Installing Default Model';
			$drop = array('users','user_roles','persons','menus','user_log');
			$init = array(
					'session' => 'session',
					'applications' => 'application',
					'roles' => 'roles',
					'persons' => 'person',
					'user_external' => 'userexternal',
					'companies' => 'companies',
					'user_companies' => 'usercompanies',
					'lookup' => 'lookup',
					'users' => 'user',
					'user_log'=>'userlog',
					'user_roles' => 'userroles',
					'role_privs' => 'roleprivs',
					'user_privs' => 'userprivs',
					'menus' => 'menus',
					'syskeys' => 'syskeys',
					'sysvals' => 'sysvals',
					'comment' => 'comment',
					'contents' => 'contents',
					'modules' => 'modules',
					'recentlog' => 'recentlog',
					'modules' => 'modules'
			);
			$q = new DBQuery (CGAF::getDBConnection());
			foreach($drop as $k) {
				$q->exec('drop table if exists ' . $k);
			}
			foreach ($init as $k => $v) {
				try {
						
					$installlog [] = 'loading model ' . $v;
					$this->getModel($v);
				} catch (\Exception $e) {
					throw $e;
				}
			}

			$s = $posted ['step_2'];
			$sql = array();
			$sql [] = 'INSERT INTO   `#__users` (`user_id`,`user_name`,`user_password`,`user_status`,`user_state`) VALUES (' . $q->quote('1')
			. ',' . $q->quote($s ['user_name']) . ',' . $q->quote($this->getAuthentificator()->encryptPassword($s['user_password']))
			. ',1,1)';
			//TODO Configurable from install, Default as developer
			$sql [] = 'INSERT INTO `#__user_roles`     			VALUES (2,' . $q->quote(\CGAF::APP_ID) . ', 1, 1)';
			$sql [] = 'INSERT INTO `#__persons` (`person_id`,`first_name`,person_owner,isprimary) VALUES (1, ' . $q->quote('Administrators') . ',1,true)';
			$q->exec($sql);
			$this->assign('installlog', $installlog);

			$gconf->setConfig('cgaf.installed', true);
			$gconf->setConfig("disableacl",false);
			if ($gconf->save(CGAF_PATH . 'config.php')) {
				Session::remove('install.postedvalues');
			}
			Session::reStart();
			\Response::Redirect(\URLHelper::add(BASE_URL, null, '__appId=' . \CGAF::APP_ID));
		}
		return parent::Run();
	}
	function handleError(Exception $ex) {
		echo $ex->getMessage();
		Response::Flush(true);
		CGAF::doExit();
		return true;
		
	}
}

$app = new Install ();
CGAF::run($app, true);
?>

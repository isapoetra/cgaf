<?php
namespace System\Controllers;
use \System\Exceptions\SystemException;
use \System\Documents\Image;
use System\ACL\ACLHelper;
use System\Session\Session;
use System\MVC\Controller;
use Request;

class AppController extends Controller {
	function isAllow($access = 'view') {
		switch ($access) {
			case 'select':
			case 'thumb':
			case 'changelog':
				return true;
			case 'activate' :
			case 'deactivate' :
			case 'uninstall' :
			case 'install' :
			case 'manage' :
			case 'update' :
			case 'unpublish':
			case 'publish':
				return \CGAF::isAllow('system', 'manage', ACLHelper::ACCESS_MANAGE);
				break;
		}
		return parent::isAllow($access);
	}
	function dumpdb() {
		$msg = '';
		try {
			$instance = \AppManager::getInstance(\Request::get('id'));
			$msg= $instance->dumpDB();
			if ($msg) {
				$msg= 'Data Dumped to directory '.$msg;
			}
		}catch (Exception $e) {
			$msg=$e->getMessage();
		}
		return parent::renderView('manage',array('msg'=>$msg));
	}
	function thumb() {
		$id = \Request::get('id');
		$img = null;
		$app =null;
		try {
			$app = \AppManager::getInstance($id);
			if ($app) {
				$img = $app->getAsset('app.png');
			} else {
				throw new SystemException('Cannot load Application ' . $id);
			}
			if (!$app) {
				throw new SystemException('Cannot load Application ' . $id);
			}
		} catch (\Exception $e) {
			if (CGAF_DEBUG) {
				throw $e;
			}
		}
		$size = \Request::get('size');
		if (!$img) {
			$img = ASSET_PATH . 'images/app.png';
		}
		$fcache = $path = $app->getAssetCachePath().'images/'. hash('crc32', $img . '_' . $size) . \Utils::getFileExt($img);
		\Utils::makeDir(dirname($fcache));
		if (!is_file($fcache)) {
			$i = new Image($img);
			$i->resize($size, $fcache);
		}
		if (!is_file($fcache)) {
			ppd($fcache);
		}
		\Streamer::Stream($fcache);

	}

	function changelog() {
		$m = $this->getModel('changelog');
		$m->Where('app_id=' . $m->quote('appid'));
		$rows = $m->loadObjects();
		return parent::renderView(
				'changelog', array(
						'rows' => $rows,
						'appOwner' => \AppManager::getInstance(\Request::get('appid')))
		);
	}

	function activate() {
		$id = \Request::get('id');
		\AppManager::activateApp($id);
		return parent::renderView('manage');
	}

	function update() {
		$id = \Request::get('appid');
		$instance = \AppManager::getInstance($id);
		$cfg = $this->getConfig('app.updatesite', null);
		$v = $instance->getAppInfo()->app_version;
		if (!$cfg) {
			$cfg = \URLHelper::add(\CGAF::getConfig('cgaf.updatesite'), null, 'type=app&id=' . $id . '&v=' . $v);
		} else {
			$cfg = \URLHelper::add($cfg, null, 'type=app&id=' . $id . '&v=' . $v);
		}
		$step = 'checkversion';

		if (!$instance->isValidToken()) {
			$ver = \URLHelper::add($cfg, null, 'step=' . $step . '&__data=json');
			$content = file_get_contents($ver);
			$params = array();
			$params['step'] = $step;
			$params['result'] = $content;
			return parent::renderView('update', $params);
		}
	}

	function recheck() {
		$id = \Request::get('appid');
		if ($r = \AppManager::getInstance($id)->performCheck()) {
			return parent::renderView(__FUNCTION__, array('errors' => $r));
		}
		return parent::renderView('manage');
	}

	function deactivate() {
		$id = \Request::get('id');
		\AppManager::activateApp($id, false);
		return parent::renderView('manage');
	}

	function uninstall() {
		$id = \Request::get('id');
		//ppd($this->getModel('recentlog'));
		if (\AppManager::isAppInstalled($id, false)) {
			\AppManager::uninstall($id);
		}
		return parent::renderView('manage');
	}

	function install() {
		$id = \Request::get('id');
		if (!$id) {
			if ($this->getAppOwner()->isValidToken()) {
				$mode = \Request::get('__mode');
				$dirs = \Request::get('dir');
				foreach($dirs as $dir) {
					\AppManager::install($dir);
				}
			}
			return parent::renderView(__FUNCTION__);
		}
		if (!\AppManager::isAppInstalled($id)) {
			\AppManager::install($id);
		}
		return parent::renderView('manage');
	}

	private function redirectToManage() {
		\Response::redirect(\URLHelper::add(BASE_URL, '/app/manage/'));
	}

	function publish() {
		\AppManager::publish(\Request::get('appid'), true);
		$this->redirectToManage();
	}

	function unpublish() {
		\AppManager::publish(\Request::get('appid'), false);
		$this->redirectToManage();
	}

	function select() {
		$id = \Request::get('appid');
		$instance = null;
		try {
			$instance = \AppManager::getInstance($id);
		} catch (\Exception $e) {
		}
		if ($instance) {
			Session::set('__appId', $id);
			setcookie('__appId', $id);
			\Response::Redirect(BASE_URL);
		}
	}

	function Index() {
		return parent::renderView('manage');
	}
}

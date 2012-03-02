<?php
namespace System\Controllers;
use System\ACL\ACLHelper;
use System\Session\Session;
use System\MVC\Controller;
use Request;

class AppController extends Controller {
  function isAllow($access = 'view') {
    switch ($access) {
      case 'select':
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
        return \CGAF::isAllow('system', 'manage', ACLHelper::ACCESS_MANAGE)
          || \CGAF::getACL()
            ->isInRole(ACLHelper::DEV_GROUP);
        break;
    }
    return parent::isAllow($access);
  }

  function changelog() {
    $m = $this->getModel('changelog');
    $m->Where('app_id=' . $m->quote('appid'));
    $rows = $m->loadObjects();
    return parent::renderView(
      'changelog', array(
        'rows'    => $rows,
        'appOwner'=> \AppManager::getInstance(\Request::get('appid')))
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
      if ($content) {
        $params = $content;
      }
      return parent::renderView('update/' . $step, $params);
    }
    ppd($cfg);
  }

  function deactivate() {
    $id = \Request::get('id');
    \AppManager::activateApp($id, false);
    return parent::renderView('manage');
  }

  function uninstall() {
    $id = \Request::get('id');
    if (\AppManager::isAppInstalled($id, false)) {
      \AppManager::uninstall($id);
    }
    return parent::renderView('manage');
  }

  function install() {
    $id = \Request::get('id');
    if (!\AppManager::isAppInstalled($id)) {
      \AppManager::install($id);
    }
    return parent::renderView('manage');
  }

  private function redirectToManage() {
    \Response::redirect(\URLHelper::add(BASE_URL, '/app/manage/'));
  }

  function publish() {
    \AppManager::publish(\Request::get('appid'));
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
    $route = $this
      ->getAppOwner()
      ->getRoute();
    switch ($route ['_a']) {
      case 'index' :
        return parent::Index();
      default :
        $app = \AppManager::getInstanceByPath($route ['_a']);
        if ($app) {
          $r = array();
          foreach ($_REQUEST as $k => $v) {
            if ($k !== '__url') {
              $r [$k] = $v;
            }
          }
          Session::set('__appId', $app->getAppId());
          \Response::Redirect(\URLHelper::addParam(BASE_URL, $r));
          return;
        }
        break;
    }
    return parent::Index();
  }
}

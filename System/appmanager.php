<?php
use System\Exceptions\AccessDeniedException;

use System\Session\Session, System\ACL\ACLHelper;
use System\Exceptions\SystemException;
use System\DB\DBQuery;
use System\Configurations\Configuration;
use System\Applications\IApplication;

abstract class AppManager extends \StaticObject {
  private static $_instances = array();
  private static $_initialized = false;
  /**
   * Enter description here...
   *
   * @var IApplication
   */
  private static $_activeApp = null;
  /**
   * @var \System\MVC\Model
   */
  private static $_model;
  private static $_appInfos = array();
  private static $_installedApps;

  /**
   * @static
   *
   * @param $appId string|\System\Applications\IApplication
   */
  public static function setActiveApp($appId) {
    if (is_object($appId) && $appId instanceof IApplication) {
      $id = $appId->getAppId();
      self::$_activeApp = $id;
      if (!isset (self::$_instances [$id])) {
        self::$_instances [$id] = $appId;
      }
      $appId = $id;
    }
    Session::set("__appId", $appId);
  }

  private static function getDefaultAppId() {
    return CGAF::getConfig("cgaf.defaultAppId", \CGAF::APP_ID);
  }

  /**
   * @static
   *
   * @param null $appId
   *
   * @return System\Applications\IApplication
   * @throws System\Exceptions\SystemException
   */
  public static function getInstance($appId = null) {
    if ($appId instanceof \IApplication) {
      $id = $appId->getAppId();
      if (!$id) {
        throw new SystemException ('Unable to get application id from class ' . get_class($appId));
      }
      if (!isset (self::$_instances [$id])) {
        self::$_activeApp = $id;
        Session::set("__appId", $id);
        self::$_instances ["$id"] = $appId;
      }
      return $appId;
    }
    $appInfo = null;
    if ($appId == null) {
      if (self::$_activeApp) {
        $appId = self::$_activeApp;
      } else {
        $appId = Session::get('__appId', self::getDefaultAppId());
        if ($appId !== null && $appId !=='') {
          $appInfo = self::getAppInfo($appId, false);
          if (!CGAF::isAllow($appId, ACLHelper::APP_GROUP)) {
            $appId = \CGAF::APP_ID;
            $appInfo = self::getAppInfo(CGAF::APP_ID, false);
          } elseif ($appInfo) {
            $appId = $appInfo->app_id;
          } else {
            throw new SystemException ('unable to get Application ' . $appId);
          }
        }
      }
    }
    if (!$appId) {
      throw new SystemException ('unable to get Application ' . $appId);
    }
    if (isset (self::$_instances [$appId])) {
      return self::$_instances [$appId];
    }
    if ($appInfo == null) {
      $appInfo = self::getAppInfo($appId, false);
      if (!$appInfo) {
        return null;
      }
    }
    if (!CGAF::isAllow($appId, ACLHelper::APP_GROUP)) {
      throw  new AccessDeniedException();
    }
    // $appName = $appInfo->app_class_name ? $appInfo->app_class_name :
    // $appInfo->app_name;
    $appShortName = $appInfo->app_path ? $appInfo->app_path : $appInfo->app_name;

    $appPath = \Utils::toDirectory((is_dir($appInfo->app_path) ? $appInfo->app_path : CGAF_APP_PATH . DS . $appInfo->app_path) . DS);
    $cName = self::getAppClass($appInfo->app_class_name, false, $appPath);
    if (!$cName) {
      if ($appInfo->app_id === CGAF::APP_ID) {
        $sappPath = CGAF_APP_PATH . DS . 'desktop' . DS . CGAF_CONTEXT . DS . "index" . CGAF_CLASS_EXT;
        $cName = "System\\Applications\\Desktop\\WebApp";

        if (!class_exists($cName, false) && is_file($sappPath)) {
          require $sappPath;
        }
        if (!class_exists($cName, false)) {
          throw new SystemException('CGAF Desktop not found');
        }

      } else {
        throw new SystemException('unable to get Application Class '.$appInfo->app_class_name.(CGAF_DEBUG ?' @'.$appPath:''));
      }
    }

    // CGAF::addStandardSearchPath(null, $appPath .DS. 'classes' ,false);
    CGAF::addClassPath('apps', $appPath . 'classes' . DS);
    /**
     * @var  \System\Applications\IApplication $instance
     */
    $instance = new $cName();
    $instance->setAppInfo($appInfo);
    if ($instance->Initialize()) {
      self::$_instances [$appId] = $instance;
      if (!self::$_activeApp) {
        self::$_activeApp = $appId;
      }
      self::trigger("onAppLoaded", $instance);
      return self::$_instances [$appId];
    } else {
      throw new SystemException ('Unable to Initialize Application');
    }
  }

  public static function getInstanceByPath($path, $throw = true) {
    $model = self::getModel(true);
    // $model->setFilterACL ( FALSE );
    $model->Where('app_path=' . $model->quote($path));
    $o = $model->loadObject();
    if ($o) {
      // check for security
      return self::getInstance($o->app_id);
    }
    return null;
  }

  /**
   * @static
   *
   * @param      $id string|\System\Applications\IApplication
   * @param bool $throw
   *
   * @return null
   * @throws System\Exceptions\SystemException
   */
  public static function getAppInfo($id, $throw = true) {

    $id = $id ? $id : self::getDefaultAppId();

    $direct = false;
    if ($id instanceof IApplication) {
      $id = $id->getAppId();
      $direct = true;
    }
    if (isset (self::$_appInfos [$id])) {
      return self::$_appInfos [$id];
    }
    if (\CGAF::isInstalled()) {
      $model = self::getModel()
      ->clear();
      if ($direct || !self::isAppStarted()) {
        $model->setFilterACL(FALSE);
      }
      $model->Where('app_id=' . $model->quote($id));
      $o = $model->loadObject();
      if ($o) {
        $infos [$o->app_id] = $o;
      } else {
        if ($throw) {
          throw new SystemException ("Application $id not found");
        } else {
          return null;
        }
      }
    } elseif (self::$_activeApp) {
      ppd(self::$_activeApp);
      return self::$_activeApp;
    }

    return $infos [$o->app_id];
  }

  /**
   * Enter description here .
   * ..
   *
   * @return String
   */
  public static function getActiveApp() {
    return self::$_activeApp;
  }

  public static function publish($id, $publish = true) {
    $cn = new DBQuery(\CGAF::getDBConnection());
    $cn->addTable('role_privs');
    $cn
    ->where('role_id=' . $cn->quote(ACLHelper::GUEST_ROLE_ID))
    ->where('app_id=' . $cn->quote(\CGAF::APP_ID))
    ->where('object_id=' . $cn->quote($id))
    ->Where('object_type=' . $cn->quote(ACLHelper::APP_GROUP))
    ->Where('privs>0');
    $o = $cn->loadObject();
    if ($publish && !$o) {
      \CGAF::getACL()->grantToRole($id, ACLHelper::APP_GROUP, ACLHelper::GUEST_ROLE_ID, \CGAF::APP_ID, 'view');
      return true;
    } elseif (!$publish && $o) {
      \CGAF::getACL()->revokeFromRole($id, ACLHelper::APP_GROUP, \CGAF::APP_ID, ACLHelper::GUEST_ROLE_ID);
      \CGAF::getACL()
      ->clearCache();
    }
    return false;
  }

  public static function getCurrentAppInfo() {
    return self::getAppInfo(
        self::getInstance()
        ->getAppName(), true
    );
  }

  public static function isAppStarted() {
    return self::$_activeApp != null;
  }

  public static function Shutdown() {
    if (self::$_activeApp != null) {
      self::getInstance()
      ->Shutdown();
    }
  }

  public static function getContent($position = null) {
    return self::getInstance()
    ->getContent($position);
  }

  public static function isAppInstalled($appPath, $bypath = true) {
    // $q = CGAF::getConnector("applications");
    $m = self::getModel();
    $m->setFilterACL(self::isAppStarted());
    if ($bypath) {
      $rappPath  = $appPath;
      if (!is_dir($appPath)) {
        $appPath = CGAF_APP_PATH . DS . $appPath;
      }
      $appPath = \Utils::ToDirectory($appPath.DS);

      $m->Where("app_path=" . $m->quote($appPath).' or app_path='.$m->quote($rappPath));
    } else {
      if ($appPath === CGAF::APP_ID) {
        return true;
      }
      $m->where('app_id=' . $m->quote($appPath));
    }
    $o = $m->loadObject();
    return $o;
  }

  /**
   * @static
   * @param $appId
   * @return bool|null|System\DB\DBQuery
   * @deprecated
   */
  public static function isAppIdInstalled($appId) {
    return self::isAppInstalled($appId, false);
  }

  private static function getAppClass($appName, $throw = true, $appPath = null) {
    $cName = $appPath ? $appName : CGAF_CLASS_PREFIX . "{$appName}App";
    $classSearch = array($cName,
        '\\System\\Applications\\' . $appName,
        '\\System\\Applications\\' . $appName . 'App');
    foreach($classSearch as $c) {
      if (class_exists($c,false)) {
        return $c;
      }
    }


    if (!class_exists($cName, false)) {
      $basePath = Utils::toDirectory($appPath ? $appPath : CGAF_APP_PATH . DS . $appName . DS);
      $clsfile = $basePath . $appName . '.class' . CGAF_CLASS_EXT;
      if (is_file($clsfile)) {
        require $clsfile;
      }
      $appFileName = $basePath . "index" . CGAF_CLASS_EXT;
      if (is_file($appFileName)) {
        require ($appFileName);
      } else {
        $alt = $basePath . DS . $appName . CGAF_CLASS_EXT;
        if (is_file($alt)) {
          require $alt;
        } elseif ($throw) {
          Logger::Error("Application File Not Found." . Logger::WriteDebug("  @%s | %s"), $appFileName, $alt);
        } else {
          Logger::Warning("Application File Not Found." . Logger::WriteDebug("  @%s | %s"), $appFileName, $alt);
        }
      }
    }
    $cl = '\\System\\Applications\\' . $appName;
    if (class_exists($cl, false)) {
      return $cl;
    }
    $cl = '\\System\\Applications\\' . $appName . 'App';
    if (class_exists($cl, false)) {
      return $cl;
    }

    if (!class_exists($cName, false)) {
      return null;
    }
    //if (!class_exists($cName, false)) {
    //	$cName = '\\System\\MVC\\Application';
    //}
    return $cName;
  }

  public static function getAppConfig($path) {
    $cf = Utils::ToDirectory($path . DS . "config.php");
    $config = new Configuration (null, false);
    if (is_file($cf)) {
      $config->loadFile($cf);
    }
    return $config;
  }

  /**
   * @static
   *
   * @param $id string|IApplication
   * @param $force bool
   * @return mixed
   */
  public static function uninstall($id, $force = false) {
    if (is_array($id)) {
      foreach ($id as $i) {
        self::uninstall($i);
      }
      return;
    }
    $continue = true;
    if ($id instanceof IApplication) {
      if (!$id->Uninstall() && !$force) {
        throw new SystemException('unable to uninstall applications');
        $continue = false;
      }
      $id = $id->getAppId();
    }
    if ($continue) {
      $q = new DBQuery (CGAF::getDBConnection());
      $qid = $q->quote($id);
      $q->exec(
          'delete from #__sysvals where sysval_key_id in (select syskey_id from #__syskeys where app_id=' . $qid . ')'
      );
      $q->exec('delete from #__syskeys where app_id=' . $qid);
      // content related
      $q->exec('delete from #__comment where app_id=' . $qid);
      $q->exec('delete from #__lookup where app_id=' . $qid);
      $q->exec('delete from #__contents where app_id=' . $qid);
      $q->exec('delete from #__modules where app_id=' . $qid);
      $q->exec('delete from #__recentlog where app_id=' . $qid);
      $q->exec('delete from #__menus where app_id=' . $qid);
      // Privs
      $q->exec('delete from #__role_privs where app_id=' . $q->quote(\CGAF::APP_ID) . ' and object_id=' . $qid);
      $q->exec('delete from #__role_privs where app_id=' . $qid);
      $q->exec('delete from #__user_privs where app_id=' . $qid);
      $q->exec('delete from #__user_roles where app_id=' . $qid);
      $q->exec('delete from #__roles where app_id=' . $qid);
      $q->exec('delete from #__applications where app_id=' . $qid);
    }
  }

  public static function activateApp($id, $active = true) {
    if (self::isAllowApp($id)) {
      //TODO Recheck from applications
      $q = new DBQuery (CGAF::getDBConnection());
      $q->exec('update #__applications set active=' . ($active ? '1' : '0') . ' where app_id=' . $q->quote($id));
    }
  }

  /**
   * Enter description here .
   * ..
   *
   * @param $appName String|IApplication
   *
   * @throws Exception
   * @return String id
   */
  public static function install($appName) {
    $instance = null;
    \CGAF::getACL()
    ->clearCache();
    if ($appName instanceof IApplication) {
      $instance = $appName;
      $appName = $appName->getAppName();
      $appPath = $instance->getAppPath();
      $config = $instance->getConfigInstance();
    } else {
      if (is_dir($appName)) {
        $appPath = realpath($appName) . DS;
        $appName = trim(substr($appName, strrpos($appName, DS)), DS);
      } else {
        $appPath = realpath(CGAF_APP_PATH . DS . $appName . DS) . DS;
      }

      $config = self::getAppConfig($appPath);
      $c = self::getAppClass($appName, true, $appPath);
      $instance = new $c ();
    }
    $id = $instance->getConfig("app.id");

    if (!$id) {
      $id = GUID::getGUID();
      $config->setConfig("app.id", $id);
      $config->setConfigFile($instance->getAppPath() . 'config.php');
      $config->Save();
    }


    if (!self::isAppInstalled($appPath)) {
      if (self::isAppIdInstalled($id)) {
        return $id;
      }
      $class = str_replace('System\\Applications\\', '', get_class($instance));
      /**
       * @var $app \System\MVC\Model
       */
      $app = self::getModel()
      ->clear();
      $app->app_id = $id;
      $app->app_class_name = $class;
      $app->app_short_name = $appName;
      $app->active = true;
      $app->app_name = $config->getConfig("app.name", $appName);
      $app->app_path = $appPath;
      $app->app_version = $config->getConfig("app.version", "0.1");
      $app->store(false);
      if ($app->getError()) {
        throw new Exception ($app->getError());
      }
      if (!$instance->Install()) {
        self::uninstall($instance, false);
        throw new SystemException ('Unable to Install application ' . $id);
      }
    }
    return $id;
  }

  /**
   * @static
   *
   * @param bool $clear
   *
   * @return \System\MVC\Model
   */
  private static function getModel($clear = true) {
    if (!self::$_model) {
      self::$_model = new \System\Models\Application (CGAF::getDBConnection());
    }
    if ($clear) {
      self::$_model->clear();
    }
    return self::$_model;
  }

  public static function allowedApp() {
    $rows = AppManager::getInstalledApp();
    return self::isAllowApp($rows);
  }

  public static function isAllowApp($o, $access = 'view') {
    $acl = CGAF::getACL();
    if (is_array($o)) {
      $r = array();
      foreach ($o as $v) {
        $v = self::isAllowApp($v, $access);
        if ($v) {
          $r [] = $v;
        }
      }

      return $r;
    } elseif (is_object($o)) {
      if (self::isAllowApp($o->app_id, $access)) {
        $path = self::getAppPath($o);

        if (!is_dir($path)) {
          return null;
        }
        return $o;
      }
    } elseif (is_string($o) || is_numeric($o)) {
      if ($o === \CGAF::APP_ID || ( int )$o === -1) {
        return true;
      }
      return ACLHelper::isInrole(ACLHelper::DEV_GROUP) || CGAF::isAllow($o, ACLHelper::APP_GROUP, $access);
    }

  }

  public static function getInstalledApp($activeOnly=true) {
    if (self::$_installedApps == null) {
      $installed = self::getModel()
      ->clear();
      if (CGAF::isInstalled() && $activeOnly) {
        $installed->where("active=" . $installed->quote('1'));
      }
      $installed->where('app_id <> ' . $installed->quote(\CGAF::APP_ID));
      $installed = $installed->loadObjects();

      if (CGAF::isInstalled()) {
        self::$_installedApps = self::isAllowApp($installed);
      }
    }
    return self::$_installedApps;
  }

  protected static function isAppPathInstalled($path) {
    $installed = self::getInstalledApp();
    foreach ($installed as $app) {
      if ($app->app_path == $path) {
        return true;
      }
    }
    return false;
  }

  public static function getNotInstalledApp() {
    $retval = array();
    $files = Utils::getDirList(CGAF_APP_PATH);
    foreach ($files as $file) {
      if (strpos($file, '.') !== false || $file === 'desktop' || $file === 'installer') {
        continue;
      }
      if (!self::isAppInstalled(CGAF_APP_PATH.DS.$file)) {
        $retval [] = $file;
      }
    }
    return $retval;
  }

  public static function initialize() {
    if (self::$_initialized) {
      return true;
    }
    /*
     * ppd(CGAF::getConfigs('Session.configs'));
    * ini_set('session.use_cookies', '0'); ini_set ( "session.auto_start",
        * false ); ini_set ( "session.use_only_cookies", false );
    */
    Session::Start();
    self::$_initialized = true;
  }

  public static function getAppPath($AppName = null) {
    if ($AppName === null) {
      $AppName = self::$_activeApp;
    }
    $obj = new stdClass ();
    if (!is_object($AppName)) {
      $obj = self::getAppInfo($AppName, false);
    } else {
      $obj = $AppName;
    }
    $path = null;
    if (isset ($obj->app_id)) {
      if (is_dir($obj->app_path)) {
        $path = $obj->app_path;
      } else {
        $path = Utils::ToDirectory(CGAF_APP_PATH . DS . $obj->app_path . DS);
      }
    } else {
      return null;
    }
    $path = Utils::ToDirectory($path);
    return $path;
  }
}

?>

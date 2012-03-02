<?php
namespace System\Installer;
use System\Models\Application;

use System\Models\RolePrivsModel;

use System\DB\DBQuery;

use System\Models\UserPrivs;

use System\Models\UserRoles;

use System\Models\RolesModel;

use System\Models\SessionModel;

use System\Models\User;

use System\Configurations\Configuration;
use System\Configurations\IConfiguration;
use CGAF;
use Response, Utils;
abstract class AbstractInstaller extends \BaseObject implements \IRenderable {
	private $_configs;
	private $_verbose;
	private $_logFile;
	private $_initialized = false;
	private $_basePath;
	function __construct($basePath, IConfiguration $configs = null) {
		$this->_basePath = $basePath;
		$this->_configs = $configs;
		$this->Initialize ();
	}
	function getBasePath() {
		return $this->_basePath;
	}
	
	protected function Initialize() {
		if ($this->_initialized) {
			return true;
		}
		$this->_initialized = true;
		
		$this->_verbose = $this->getConfig ( 'verbose', CGAF_DEBUG );
		$this->_logFile = $this->getConfig ( 'logfile', CGAF_PATH . 'install.log' );
		if (is_file ( $this->_logFile )) {
			unlink ( $this->_logFile );
		}
		return $this->_initialized;
	}
	function geConfiguration() {
		if (! $this->_configs) {
			$this->_configs = new Configuration ( null, false );
			$this->_configs->loadFile ( $this->_basePath . 'install.config.php' );
		}
		return $this->_configs;
	}
	function getConfig($name, $def = null) {
		return $this->geConfiguration ()->getConfig ( $name, $def );
	}
	
	private function parse($v) {
		if ($v === null) {
			return null;
		}
		if (is_bool ( $v )) {
			return $v ? 'Yes' : 'No';
		}
		return $v;
	}
	private function log($msg, $r = null, $req = null, $current = null, $info = null) {
		$resp = Response::getInstance ();
		$title = $this->parse ( $r );
		$current = $this->parse ( $current );
		$req = $this->parse ( $req );
		if ($r && ! $this->_verbose) {
			return;
		}
		
		$status = $resp->writeOkNo ( $r, true );
		$resp->writeLn ( $msg . ($current !== null || $req !== null ? " ( $current = $req) " : '') . ($r !== null ? '....... ' . $status : '') );
		file_put_contents ( $this->_logFile, implode ( ',', func_get_args () ) . "\n", FILE_APPEND );
		if (! $r && $info) {
			$resp->writeColor ( "\t$info", 'yellow', null, 'underline' );
		}
	}
	function compareVersion($title, $configname, $installed, $def = null) {
		$req = $this->getConfig ( $configname, $def );
		$r = version_compare ( $installed, $req ) >= 0;
		$this->log ( $title, $r, $req, $installed );
		return $r;
	}
	private function checkLibs() {
		$libs = $this->getConfig ( 'php.libs' );
		$dyn = ini_get ( 'enable_dl' );
		Response::writeln ( $dyn );
		$this->log ( 'Enable Dynamic loading library', $dyn, true, $dyn, 'change paramemeter enable_dl in php.ini' );
		$this->log ( 'Testing Load required Library', null );
		$ok = true;
		foreach ( $libs as $k => $lib ) {
			$should = true;
			$info = null;
			if (! is_numeric ( $k )) {
				$info = is_array ( $lib ) ? (isset ( $lib ['info'] ) ? $lib ['info'] : null) : null;
				$old = $lib;
				$lib = $k;
				if (is_array ( $old )) {
					$should = isset ( $old ['value'] ) ? $old ['value'] : true;
				} else {
					$should = $old;
				}
			}
			$r = extension_loaded ( $lib ) || Utils::loadLib ( $lib );
			$ok = $ok & $r;
			$info = $info ? "\t" . $info : null;
			$this->log ( "\tLibrary " . $lib, $r == $should, $should, $r, $info );
		}
		return $ok;
	}
	function checkCompat() {
		Response::clearBuffer ();
		$info = Utils::getOSInfo ();
		$r = $this->compareVersion ( 'Checking PHP Version', 'php.versionmin', PHP_VERSION );
		if (strtoupper ( substr ( PHP_OS, 0, 3 ) ) === 'WIN') {
			$this->log ( 'WARNING!!! this framework not full tested in your Operating System (' . PHP_OS . ') please use linux instead', null );
		}
		$r = $r & $this->checkLibs ();
		$r = $r & $this->checkPath ();
		return $r;
	}
	private function checkPath() {
		$this->log ( 'Checking File Permission' );
		$paths = $this->GetConfig ( 'path' );
		// var_dump($paths);
		$tocheck = $this->getConfig ( 'config.path' );
		foreach ( $paths as $k => $v ) {
			$p = $v;
			$check = $tocheck;
			if (! is_numeric ( $k )) {
				$p = $k;
				$check = array_merge ( $tocheck, $v );
			}
			$this->Log ( 'Checking file Integrity ' . $p );
			$stat = Utils::getFileInfo ( $p );
			foreach ( $check as $k => $v ) {
				$info = null;
				if (is_array ( $v )) {
					$info = isset ( $v ['info'] ) ? "\t" . $v ['info'] : null;
					$v = isset ( $v ['value'] ) ? $v ['value'] : null;
					if ($v == null) {
						$this->log ( 'unknown value to check for ' . $k );
						continue;
					}
				}
				switch ($k) {
					case 'owner_usergroup' :
						if (! \System::isLinuxCompat ()) {
							$this->log ( "\t!!!!INCOMPATIBLE OS!!! check " . $k, true, $v, $stat->$k, $info );
							break;
						}
					default :
						$this->log ( "\tcheck " . $k, $stat->$k == $v, $v, $stat->$k, $info );
						break;
				}
			
			}
		}
	}
	private function installACL() {
		$conn = CGAF::getDBConnection ();
		$conn->Drop ( 'sessions' );
		$conn->Drop ( 'roles' );
		$conn->Drop ( 'users' );
		$conn->Drop ( 'role_privs' );
		$conn->Drop ( 'user_privs' );
		$u = new User ( $conn );
		
		new SessionModel ( $conn );
		new RolesModel ( $conn );
		new UserRoles ( $conn );
		new RolePrivsModel ( $conn );
		new UserPrivs ( $conn );
	}
	private function installCore() {
		
		$q = new DBQuery ( CGAF::getDBConnection () );
		if ($q->isObjectExist ( "applications" )) {
			// $q->drop("applications")->exec();
			// $q->drop("users")->exec();
		}
		if (! $q->isObjectExist ( "applications" )) {
			using ( 'System.AppModel.MVC.MVCModel' );
			using ( "System.Models" );
			new Application ( self::getDBConnection () );
			new User ( self::getDBConnection () );
		}
		$ignore = CGAF::getConfig ( 'install.ignoreapp', array (
				'chart', 
				'cgii', 
				'cms-admin', 
				'mfh-1.5', 
				'wordtube', 
				'Test' 
		) );
		$conn = CGAF::getConnector ();
		$dirs = Utils::getDirList ( CGAF_APP_PATH . DS );
		$acl = CGAF::getACL ();
		foreach ( $dirs as $dir ) {
			if ($dir != "." && $dir != ".." && $dir != ".svn" && ! in_array ( $dir, $ignore )) {
				$id = \AppManager::install ( $dir );
				$acl->grantToRole ( $id, 'app', 1, - 1 );
			}
		}
	
	}
	function Render($return = false) {
		$this->installACL ();
		$this->installCore ();
	}
}

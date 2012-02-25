<?php
if (! defined ( 'CGAF' )) {
	include 'cgafinit.php';
}
if (CGAF::isInstalled ()) {
	die ( 'installed' );
	Response::Redirect ( 'index.php' );
	exit ( 0 );
}
use System\DB\DBQuery;
class Install extends \System\MVC\Application {
	function __construct() {
		parent::__construct ( dirname ( __FILE__ ) . DS . 'tmp/install/', 'install' );
	}
	function getAppName() {
		return 'install';
	}
	function run() {
		clearstatcache ();
		$init = array (
				'session' => 'session',
				'applications' => 'application',
				'roles' => 'roles',
				'users' => 'user',
				'role_privs' => 'roleprivs',
				'user_privs' => 'userprivs',
				'menus' => 'menus'
		);
		$q = new DBQuery ( CGAF::getDBConnection () );
		foreach ( $init as $k => $v ) {
			$q->exec ( 'drop table if exists ' . $k );
			echo 'loading model ' . $v . '<br/>';
			$m = $this->getModel ( $v );
			if (! $m) {
				ppd ( $v );
			}
		}
		if (! CGAF_DEBUG) {
			CGAF::getConfiguration ()->setConfig ( 'installed', true );
			CGAF::getConfiguration ()->save ( CGAF_PATH . 'config.php' );
			Response::Redirect ();
		}
	}
}
$app = new Install ();
CGAF::run ( $app, true );
?>
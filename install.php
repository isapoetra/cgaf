<?php
if (! defined ( 'CGAF' )) {
	include 'cgafinit.php';

}
if (CGAF::isInstalled ()) {
	dj ( 'installed' );
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
		$init = array (
				'applications' => 'application', 
				'roles' => 'roles', 
				'role_privs' => 'roleprivs', 
				'user_privs' => 'userprivs' 
		);
		$q = new DBQuery ( CGAF::getDBConnection () );
		$f = CGAF::getInternalStorage ( 'install/db/', false, true );
		foreach ( $init as $k => $v ) {
			try {
				$q->exec ( 'drop table ' . $k );
			} catch ( Exception $e ) {
			
			}
			$m = $this->getModel ( $v );
		}
		
		CGAF::getConfiguration ()->setConfig ( 'installed', true );
		CGAF::getConfiguration ()->save ( CGAF_PATH . 'config.php' );
		Response::Redirect ();
	}
}

$app = new Install ();
CGAF::run ( $app, true );

?>
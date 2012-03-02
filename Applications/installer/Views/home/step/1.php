<?php
defined('CGAF') or die();
use System\Web\Utils\HTMLUtils;
$items = array (
		array (
				'value' => 'Mysql',
				'key' => 'mysql'
		)
);
echo HTMLUtils::renderSelect ( __ ( 'install.db_type' ), 'db.type', $items, null, false, null, array (
		'class' => isset ( $posterror ['db_type'] ) ? 'error' : ''
) );
echo HTMLUtils::renderTextBox ( __ ( 'install.db_host' ), 'db.host', $values ['db_host'] );
echo HTMLUtils::renderTextBox ( __ ( 'install.db_user' ), 'db.user', $values ['db_user'] );
echo HTMLUtils::renderPassword ( __ ( 'install.db_password' ), 'db.password', $values ['db_password'] );
echo HTMLUtils::renderTextBox ( __ ( 'install.db_database' ), 'db.database', $values ['db_database'] );
echo HTMLUtils::renderTextBox ( __ ( 'install.db_table_prefix' ), 'db.table_prefix', $values ['db_table_prefix'] );
?>

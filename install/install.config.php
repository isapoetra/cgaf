<?php
global $_configs;

$_configs = array(
	'step' =>array(
		'prepare'=>array(
		
		)
	),	
	'config'=>array(
		'path'=>array(
			'is_exist'=>true,
			'is_dir'=>true,
			'is_readable'=>true,
			'is_writeable'=>false,
			'perm_human'=>array(
				'value'=>'drwxrwx---',
				'info'=>'disallow access from other'
			),
			'owner_usergroup'=>array(
						'value'=>'www-data:www-data',
						'info'=>'for security reason, the owner of this folder should be www-data:www-data'
			)
		)
	),
	'php'=>array(
		'versionmin'=>'5.2',
		'libs'=>array(
			'SQLite'=>array(
				'ospackage'=>'php5-sqlite'
			),
			'sqlite3'=>array(
				'ospackage'=>'php5-sqlite'
			),
			'mcrypt',
			'imagick',
			'gd',
			'tidy'=>array(
				'value'=>false,
				'info'=>'Performance issue'
				)
		)
	),
	'path'=>array(
				CGAF_PATH=>array(
					'is_dir'=>true,
					'is_readable'=>true,
					'is_writeable'=>false),
				CGAF_PATH.'tmp',
				CGAF_APP_PATH=>array(
					'is_dir'=>true,
					'is_readable'=>true,
				)
			)

		);
?>

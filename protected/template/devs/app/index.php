<?php
$date =  date('d M Y h:i:s');
echo <<< EOT
<?php

namespace System\Applications;
use System\Applications\WebApplication;

/**
* Application Class
* @since $date
* @version 1.0
* @author Iwan Sapoetra
* @copyright Cipta Graha Informatika Indonesia
**/
class {$appName}App extends WebApplication {
	function __construct() {
		parent::__construct(dirname(__FILE__),'$appName');
	}
}
?>
EOT;

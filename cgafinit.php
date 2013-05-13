<?php
if (!defined('CGAF')) {
	include dirname(__FILE__) . '/System/cgaf.php';
	if (!CGAF::Initialize()) {
		throw new System\Exceptions\SystemException('Initializing framework');
	}
}

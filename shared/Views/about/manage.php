<?php
use System\Web\UI\JQ\FileManager;
use System\JSON\JSON;
$controller = $this->getController();
$path = $controller->getContentPath();
$fm = new FileManager('about-fm');
$fm->setBasePath($path);
$fm->setCanEdit(true);
$fm->setConfig(array(
				'toolbar' => array(
						'buttonDownload' => true)));
$r = $fm->render(true);
if (!\Request::isJSONRequest()) {
	echo $r;
} else {
	echo JSON::encode($r);
}

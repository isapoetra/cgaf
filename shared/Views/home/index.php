<?php
defined("CGAF") or die("Restricted Access");
$content = $this->getContent();
if ($content) {
	echo $content;
	return;
}
$controller = $this->getController();
if ($controller) {
	$content = $controller->renderContent('center');
}
echo $content;

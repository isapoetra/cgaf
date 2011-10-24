<?php
defined("CGAF") or die("Restricted Access");
$controller = $this->getController();
$content = $this->getContent();

if (!$content && $controller) {
	$content = $controller->renderContent('center');
}
echo $content;
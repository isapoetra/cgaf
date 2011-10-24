<?php
use System\Web\UI\Controls\BreadCrumb;
if ($appOwner->getConfig('site.showcrumb', true)) {
	if (!isset($crumbs)) {
		$crumbs = $appOwner->getCrumbs();
	}
	$bc = new BreadCrumb();
	$bc->addItems($crumbs);
	echo $bc->render(true);
}

<?php
use System\Web\UI\JQ\Grid;
use System\Web\UI\JQ\Accordion;
use System\MVC\MVCHelper;
$route = MVCHelper::getRoute();
$appOwner = isset($appOwner) ? $appOwner : $this->getAppOwner();
$routename = isset($routename) ? $routename : $this->getController()->getControllerName();
$action = isset($action) ? $action : $route['_a'];
$baseLang = isset($baseLang) ? $baseLang : $routename;
$autogenerategridcolumn = isset($autogenerategridcolumn) ? $autogenerategridcolumn : true;
$gridcallback = isset($gridcallback) ? $gridcallback : null;
$openGridEditInOverlay = isset($openGridEditInOverlay) ? $openGridEditInOverlay : true;
$gridNavigatorConfig = isset($gridNavigatorConfig) ? $gridNavigatorConfig : array();
$gridId = isset($gridId) && $gridId !== null ? $gridId : $routename . '_grid';
$row = isset($row) ? $row : $this->getController()->getModel();
$columns = isset($columns) ? $columns : null;
$grid = new Grid($gridId, $this, $row, $columns);
$grid->setRoute(array(
				'_c' => $routename));
$defConfig = array(
		'height' => '250px',
		'loadComplete' => 'function(){cgaf.defaultUIHandler()}');
if (isset($gridConfigs)) {
	$gridConfigs = array_merge($defConfig, $gridConfigs);
} else {
	$gridConfigs = $defConfig;
}
$grid->setBaseLang($baseLang);
$grid->setConfig($gridConfigs);
$grid->setNavConfig($gridNavigatorConfig);
$grid->setAutoGenenerateColumn($autogenerategridcolumn);
$grid->setopenEditInOverlay($openGridEditInOverlay);
$grid->setCallback($gridcallback);
$grid->setRenderDefaultAction(true);
$links = isset($links) ? $links : array();
$header = isset($header) ? $header : null;
if (Request::isDataRequest()) {
	echo $grid->Render(true);
	return;
}
echo $header;
echo '<div class="row-fluid">';
echo '<div class="span9" style="height:300px">';
if (isset($title)) {
	echo '<h3>' . $title . '</h3>';
}
//echo '<div id="' . $routename . '-grid-container" class="' . $action . '-grid-container grid-container">';

if (isset($info)) {
	echo '<div>' . $info . '</div>';
}
echo $grid->render(true);
echo '</div>';
if (count($links)) {
	echo '<div class="well span2" style="padding: 8px 0;">';
	echo '<ul id="' . $routename . '-task-container" class="nav nav-list">';
	echo '<li class="nav-header">Actions</li>';
	//$acc = new Accordion($routename . '-taskmenu', $this);
	//$acc->setDivAttr(array(
	//				'class' => 'tasks-menu'));
	//$ac = $acc->AddAccordion('Tasks', '');
	//$acc->setConfig('fillspace', true);
	foreach ($links as $link) {
		echo '<li>'.$link.'</li>';
		//ppd($link);
		//$ac->addItem($link);
	}
	//echo $acc->render(true);
	echo '</ul>';
	echo '</div>';
}
$clink = count($links) ? '200' : 0;
$gid = $grid->getId();
if (!Request::isAJAXRequest()) {
$script = <<<EOT
var w = $('#maincontent').width();
	var tc = $('#{$routename}-task-container');
	var gc = $('#{$routename}-grid-container');
	var nw = gc.parent().width();
	if (tc.length >0 ) {
		nw -= tc.width()+20;
	}
	gc.width(nw);
	$('#$gid').setGridWidth(nw);
EOT;
$appOwner->addClientScript($script);
echo '</div>';
}

?>

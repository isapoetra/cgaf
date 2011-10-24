<?php
$logdata = isset($logdata) ? $logdata : Logger::getLogData();
if ($logdata) {
	$ui = new \System\Web\UI\Controls\Expandable("server-log");
	//$ui->setAttr("class", "ui-helper-reset ui-helper-clearfix  log-list");
	$content = '';
	$content .= '<ul>';
	foreach ($logdata as $log) {
		$content .= '<li class="log-level-' . $log['level'] . ' log-item">' . $log['message'] . '</li>';
	}
	$content .= '</ul>';
	$ui->setContent($content);
	//$ui->setConfig('content',JSUtils::addSlash());
	echo $ui->render(true);
}
?>
<?php
define('CGAF_DEBUG', true);
error_reporting(E_ALL);
include 'cgafinit.php';
use \Response;
use System\DB\DBQuery;
use System\Configurations\Configuration;
use System\DB\DB;
use System\DB\DBUtil;
if (!\System::isConsole()) {
	die('FROM CONSOLE PLEAAAAAAAAAAAAAAAAAAAAAAAAAAASE');
}
function execsql($sql, $conn = null) {
	if (!$conn) {
		$conn = CGAF::getDBConnection();
	}
	$q = new DBQuery($conn);
	$q->clear();
	$r = $q->exec($sql);
	\Response::getInstance()->writeLn($q->lastSQL());
	return $r;
}
function dumpTable($tname, $con, $prefix, $create = true, $where = null) {
	$scripts = '';
	$con = $con ? $con : \CGAF::getDBConnection();
	if ($create) {
		$qr = execsql('show create table ' . $con->quoteTable($tname), $con);
		$scripts .= $qr->first()->{"Create Table"} . ';' . PHP_EOL;
	}
	$data = execsql('select * from ' . $con->quoteTable($tname) . $where, $con);
	$d = $data->First();
	while ($d) {
		$scripts .= 'insert into ' . $con->quoteTable($tname) . ' values (';
		foreach ($d as $v) {
			$scripts .= $con->quote($v) . ',';
		}
		$scripts = substr($scripts, 0, strlen($scripts) - 1);
		$scripts .= ');' . PHP_EOL;
		$d = $data->next();
	}
	return $scripts;
}
$appName = 'mybook';
$cpref = ucfirst($appName);
$appPath = CGAF_APP_PATH . DS . $appName . DS;
$r = \Response::getInstance();
//load configurtion
$config = new Configuration(null, false);
$config->loadFile($appPath . 'config.php');
$c1 = $config->getConfigs('db');
if (!$c1) {
	ppd('Application Database Connection not found');
}
$id = $config->getConfig('app.id');
$fname = \CGAF::getInternalStorage('.upload', false, true, 0770) . DS . $id . '.sql';
$dbc = CGAF::getConfigs('db');
\Utils::arrayMerge($c1, $dbc);
\Utils::arrayMerge($c1, $config->getConfigs('db'));
$con = DB::Connect($c1);
$res = $con->getTableList();
$res->first();
$scripts = '';
$t = $res->first();
$prefix = 'c606363_mybook';
$scripts = 'use ' . $prefix . ';' . PHP_EOL;
while ($t) {
	$scripts .= dumpTable($t->table_name, $con, $prefix);
	$t = $res->next();
}
$prefix = 'c606363_cgaf';
$scripts .= 'use ' . $prefix . ';' . PHP_EOL;
$list = array(
		'applications',
		'roles',
		'user_roles',
		'role_privs',
		'contents',
		'menus',
		'recentlog');
$list = array_reverse($list);
foreach ($list as $l) {
	$scripts .= 'delete from ' . $con->quoteTable($l) . ' where app_id=' . $con->quote($id) . ';' . PHP_EOL;
}
$scripts .= 'delete from role_privs where app_id=\'__cgaf\' and object_id=' . $con->quote($id) . ' and object_type=\'app\';' . PHP_EOL;
$list = array_reverse($list);
foreach ($list as $l) {
	$scripts .= dumpTable($l, null, $prefix, false, ' where app_id=' . $con->quote($id));
}
$scripts .= dumpTable('role_privs', null, $prefix, false, 'where app_id=\'__cgaf\' and object_id=' . $con->quote($id) . ' and object_type=\'app\'');
$scripts .= 'delete from recentlog where app_id=\'__cgaf\'';
$scripts .= dumpTable('recentlog', null, $prefix, false, 'where app_id=\'__cgaf\'');
file_put_contents($fname, $scripts);
pp($scripts);

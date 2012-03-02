<?php
/**
 * User: Iwan Sapoetra
 * Date: 08/03/12
 * Time: 12:39
 */
$rows = $rows ? $rows : array();
$info = $appOwner->getAppInfo();
echo sprintf(__('app.changelog.title', 'Changelog for application %s'), $info->app_name);
foreach ($rows as $row) {
}
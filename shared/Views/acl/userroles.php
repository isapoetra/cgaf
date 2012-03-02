<?php
use System\Web\UI\JQ\Grid;
$columns = isset($columns) ? $columns : null;
$grid = new Grid('userrolesgrid', $this, $model, $columns, BASE_URL . '/acl/manage/_a/userroles');
$grid->setBaseLang('userroles');
$grid->setConfig('caption', 'User For Roles');
$grid->setConfig('multiselect', false);
$grid->setAutoGenenerateColumn(true);

echo $grid->render(true);
?>
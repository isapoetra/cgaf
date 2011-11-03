<?php
use System\Web\Utils\HTMLUtils;
/*$model = $this->getAppOwner()->getModel('menus');
$model->setIncludeAppId(false);
$model->clear();
$model->where('menu_position=' . $model->quote('footer'));
$model->where('app_id='.$model->quote('__cgaf').' or app_id'.$appOwner->getAppId);
$model->where('menu_state=1');
$model->orderBy('menu_index');*/
echo HTMLUtils::renderMenu($this->getAppOwner()->getMenuItems('footer'));

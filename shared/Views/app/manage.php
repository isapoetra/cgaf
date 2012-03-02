<?php
use System\ACL\ACLHelper;

$ctl = $this->getController();
$list = \AppManager::getInstalledApp();
$manageMode = CGAF::isAllow('system', 'manage', ACLHelper::ACCESS_MANAGE);
echo $manageMode ? '<h5>Installed Application</h5>' : '';
echo '<ul class="thumbnails" id="app-list">';
foreach ($list as $app) {
  echo '<li class="span2">';
  echo '<div class="thumbnail">';
  echo
    '<a href="' . (( int )$app->active === 1 ? \URLHelper::add(BASE_URL, '/app/select/?appid=' . $app->app_id) : '#')
      . '"  class="thumbnail" rel="tooltip" title="Click to view information">';
  echo '<img src="http://placehold.it/140x100" alt="app logo">';
  echo '</a>';
  echo '<h5>' . $app->app_name . '</h5>';
  echo '<div class="caption">';
  if ($manageMode) {
    echo '<ul class="nav nav-list">';
    echo  '<li class="nav-header">Systems</li>';
    if ($ctl->isAllow('uninstall')) {
      echo '<li><a  href="' . BASE_URL . 'app/uninstall/?id=' . $app->app_id . '">Uninstall</a></li>';
    }
    switch (( int )$app->active) {
      case 1 :
        if ($ctl->isAllow('deactivate')) {
          echo '<li><a  href="' . BASE_URL . 'app/deactivate/?id=' . $app->app_id . '">Deactivate</a></li>';
        }
        break;
      case 0 :
      case null :
        if ($ctl->isAllow('activate')) {
          echo '<li><a  href="' . BASE_URL . 'app/activate/?id=' . $app->app_id . '">Activate</a></li>';
        }
    }
    echo '<li class="nav-header">Manage</li>';
    echo '<li><a href="' . BASE_URL . 'acl/manage/?appid=' . $app->app_id . '"><i class="icon-user"></i>ACL</a></li>';
    echo'<li><a href="' . BASE_URL . 'contents/manage/?appid=' . $app->app_id
      . '"><i class="icon-gift"></i>Contents</a></li>';
    echo'<li><a href="' . BASE_URL . 'menus/manage/?appid=' . $app->app_id
      . '"><i class="icon-list-alt"></i>Menus</a></li>';
    if (!\CGAF::isAllow($app->app_id, ACLHelper::APP_GROUP, 'view', ACLHelper::PUBLIC_USER_ID)) {
      echo'<li><a href="' . BASE_URL . 'app/publish/?appid=' . $app->app_id
        . '"><i class="icon-list-alt"></i>Publish</a></li>';
      echo '<li class="nav-header">Misc</li>';
      echo'<li><a href="' . BASE_URL . 'app/update/?appid=' . $app->app_id
        . '"><i class="icon-list-alt"></i>Update</a></li>';
    }
    echo '</ul>';
  }
  echo '</div>';
  echo '</div>';
  echo '</li>';
}
echo '</ul>';
if ($manageMode) {
  echo '<h5>Not Installed Application</h5>';
  $list = \AppManager::getNotInstalledApp();
  echo '<ul class="thumbnails">';
  foreach ($list as $app) {
    echo '<li>';
    echo $app;
    echo '<div class="actions">';
    echo '<a href="' . BASE_URL . 'app/install/?id=' . $app . '">Install</a>';
    echo '<div>';
    echo '</li>';
  }
  echo '</ul>';
}
<?php
defined('CGAF') or die();
use System\Web\Utils\HTMLUtils;
echo HTMLUtils::renderTextBox (__('install.user_name'),'user.name',$values['user_name']);
echo HTMLUtils::renderPassword (__('install.user_password'),'user.password',$values['user_password']);
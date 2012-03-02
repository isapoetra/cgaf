<?php
defined('CGAF') or die();
use System\Web\Utils\HTMLUtils;
echo HTMLUtils::renderCheckbox(__('install.acl_public'), 'acl.public',$values['acl_public'],null,true);
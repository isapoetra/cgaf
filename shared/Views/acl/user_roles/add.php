<?php
use System\Web\Utils\HTMLUtils;
echo HTMLUtils::beginForm('/acl/manage/?_a=userroles&_gridAction=store');
echo HTMLUtils::endForm(true,false);
?>
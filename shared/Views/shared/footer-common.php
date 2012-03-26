<?php
use System\Web\Utils\HTMLUtils;
echo '<div class="tabbable tabs-below">';
echo \CGAF::isInstalled() ? HTMLUtils::renderMenu($this->getAppOwner()->getMenuItems('footer'),null,'nav-tabs') : '';
echo '</div>';

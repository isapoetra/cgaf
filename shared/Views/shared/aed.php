<?php
use System\Web\Utils\HTMLUtils;
$rname = $controller->getControllerName();
$etitle = ($editmode ? 'edit' : 'add');
$this->assign('formId',$formAttr['id']);
echo '<div class="' . $etitle . '-' . $rname . ' form-' . ($editmode ? 'edit' : 'add') . ' aed-' . $rname . '">';
echo '<h2>' . __($rname . '.' . $etitle, ucwords($etitle . ' ' . $rname) . ' ' . $id) . '</h2>';
echo HTMLUtils::beginForm($formAction, true, true, $msg, $formAttr);
echo $controller->renderView($editForm ? $editForm : 'add_edit', $this->getVars());
echo HTMLUtils::endForm(true, true, false);
echo '</div>';
?>
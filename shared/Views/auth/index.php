<?php
$full = isset($full) ? $full : false;
$class = $full ? "full" : "simple";
$rendercontainer = isset($rendercontainer) ? $rendercontainer : true;
$cssClass = isset($cssClass) ? $cssClass : 'account';
$renderForgot = isset($renderForgot) ? $renderForgot : false;
$allowRegister = isset($allowRegister) ? $allowRegister : $this->getAppOwner()->isAllow('register', 'system');
if ($rendercontainer) {
	echo '<div id="loginscroll">';
	echo '<div>';
}
//
echo $this->render('form/login', true, false, array(
				'cssClass' => $cssClass,
				'renderNext' => true,
				'renderFormAction' => False,
				'json' => $rendercontainer,
				'allowRegister' => $allowRegister));
if ($renderForgot) {
	echo $this->render('form/forget', true, false, array(
					'cssClass' => $cssClass,
					'renderPrev' => true,
					'renderFormAction' => False));
}
if ($rendercontainer) {
	echo '</div>';
	echo '</div>';
}
?>

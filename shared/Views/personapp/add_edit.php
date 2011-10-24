<?php
use System\Web\Utils\HTMLUtils;
use System\Web\JS\JSUtils;
echo HTMLUtils::renderTextBox(__('app_id'), 'app_id', $appOwner->getAppId(), null, false);
echo HTMLUtils::renderTextBox(__('person.person_id'), 'person_id', @$row->person_id, array(
		'__suffix' => 'a'));
$personUrl = \URLHelper::add(APP_URL,'person/sp/','autoc=true');

$script = <<< EOT
$('#$formId #person_id').autocomplete({
	minLength:3,
	source:'$personUrl'
});
EOT;
$appOwner->addClientScript($script);

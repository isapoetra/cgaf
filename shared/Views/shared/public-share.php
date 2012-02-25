<?php
use \System\API\PublicApi;
$appOwner = isset($appOwner) ? $appOwner : $this->getAppOwner();
$appOwner->addClientAsset(System\Web\JS\CGAFJS::getPluginURL('social-share'));
$appOwner->AddClientScript('$.socialshare({});');
?>
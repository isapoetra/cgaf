<?php
use \System\API\PublicApi;
$appOwner->addClientAsset(System\Web\JS\CGAFJS::getPluginURL('social-share'));
$appOwner->AddClientScript('$.socialshare({});');
?>
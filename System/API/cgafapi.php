<?php
namespace System\API;
class cgafapi extends PublicApi
{
    function init($service)
    {
        $app = \AppManager::getInstance();
        $asset = \URLHelper::addParam($app->getLiveAsset('cgaf/cgaf-api.js'), 'key=' . $app->getConfig('api.cgaf.key', '001'));
        $app->addClientAsset($asset, array(
            'id' => 'cgaf-jsapi'));
    }

    function like()
    {
        return '<div class="cgaf-like"></div>';
    }
}

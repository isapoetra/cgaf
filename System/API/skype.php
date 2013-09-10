<?php
namespace System\API;
class Skype extends PublicApi
{
    function init($service)
    {
        switch (strtolower($service)) {
            case 'onlinestatus':
                $this->getAppOwner()->addClientAsset('http://download.skype.com/share/skypebuttons/js/skypeCheck.js');
                break;
            default:
                ;
                break;
        }
        return parent::init($service);
    }

    function onlineStatus($config = null)
    {
        if (is_string($config)) {
            $config = array(
                'username' => $config);
        }
        $def = array(
            //'image' => 'call_green_white_153x63.png'
            'image' => 'bigclassic');
        $config = \Utils::arrayMerge($def, $config);
        if (!isset($config['username'])) {
            return null;
        }
        return '<a href="skype:' . $config['username'] . '?call"><img src="http://mystatus.skype.com/' . $config['image'] . '/' . $config['username'] . '" style="border: none;" alt="Skype Me™!" /><span>Skype Me™!</span></a>';
    }
}

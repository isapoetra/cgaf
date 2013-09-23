<?php
namespace System\Web\JS\Engine;

use System\Applications\IApplication;

class jQuery extends AbstractJSEngine
{
    private $_loaded = false;

    function __construct(IApplication $appOwner)
    {
        parent::__construct($appOwner, 'jQuery',
            $appOwner->getConfig('app.js.jQuery.version', '1.9.1'), $appOwner->getConfig('app.js.jQuery.compat'));
    }

    function loadUI($direct = true)
    {
        if ($this->_loaded)
            return array();
        if (!$this->getConfig('ui.enabled', true)) {
            return array();
        }
        $this->_loaded = true;
        $assets = array();
        $version = $this->getConfig('ui.version', '1.10.3');
        $ui = 'jQuery-UI/' . $version . DS;
        $assets[] = 'cgaf/cgaf-ui.js';
        if ($this->getConfig('usecdn', CGAF_DEBUG == false)) {
            $assets[] = 'http://code.jquery.com/ui/' . $version . '/jquery-ui.js'; //'jquery-ui.js';
            $assets[] = 'http://code.jquery.com/ui/' . $version . '/themes/smoothness/jquery-ui.css'; //'themes/base/jquery-ui.css';
        } else {
            $assets[] = $ui . '/jquery-ui.js'; //'jquery-ui.js';
            //$assets[] = $ui.'/themes/smoothness/jquery-ui.css';//'themes/base/jquery-ui.css';
        }
        $assets[] = 'themes/base/jquery-ui.css';
        if ($this->getConfig('js.bootstrap.enabled', true)) {
            $assets[] = 'themes/bootstrap/jquery-ui.css';
        } else {
            $theme = $this->_appOwner->getUserConfig('ui.themes', $this->_appOwner->getConfig('ui.themes', 'ui-lightness'));
            if ($theme) {
                $assets[] = 'themes/' . $theme . '/jquery-ui.css';
            }
        }
        $retval = array();
        foreach ($assets as $asset) {
            if (\Utils::isLive($asset)) {
                $r = $asset;
            } else {
                $r = $this->getAsset($ui . $asset, null, false);
                if (!$r) {
                    $r = $this->getAsset($asset, null, true);
                }
            }
            $retval[] = $r;
        }

        if ($direct) {
            $this->_appOwner->addClientAsset($retval);
        }
        return $retval;
    }

    protected function getJSAsset()
    {
        //$prefix = strtolower($this->_baseConfig);
        $assets = array('jquery.js');
        $ui = array();
        if ($this->_useui) {
            $ui = $this->loadUI(false);
        }
        \Utils::arrayMerge($assets, $ui);
        return $assets;
    }
}

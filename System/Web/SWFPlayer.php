<?php
if (!defined("CGAF"))
    die ("Restricted Access");
class TSWFPlayer extends WebControl implements IRenderable
{
    private $_appOwner;
    private $_swf;
    private $_vars = array();
    private $_params;
    private $_width = "100%";
    private $_height = "100%";
    private $_flashVersion = "9.0.124";
    private $_plugins = array();
    private $_events = array();
    private $_flashInstallURL = null;
    private $_swfCallback = null;

    function __construct($appOwner, $swf, $cid, $callback = null)
    {
        parent::__construct('div', false, array(
            'class' => "swf-player"
        ));
        $this->setId($cid);
        $this->_swfCallback = $callback;
        $this->_params = new stdClass ();
        $this->_appOwner = $appOwner ? $appOwner : AppManager::getInstance();
        $swfalt = null;
        if (CGAF_DEBUG) {
            $swfalt = 'swf/debug/' . $swf;
        }
        $swf = 'swf/' . $swf;

        //BASE_URL.'/Data/swf/playerProductInstall.swf'
        $this->_flashVersion = $appOwner->getConfig('app.flash.version', $this->_flashVersion);
        if ($swfalt) {
            $this->_swf = $appOwner->getLiveData($swfalt);
        }
        if (!$this->_swf) {
            $this->_swf = $appOwner->getLiveData($swf);
            if (!$this->_swf) {
                throw new SystemException ('unable to find player [' . $swf . ']');
            }
        }
        $this->setId($cid);
        if (CGAF_DEBUG) {
            $this->addVars("debug", "console");
        }
        $this->addVars("screencolor", $appOwner->getConfig('media.player.video.bgcolor', 'white'));
        $this->addVars("icons", "false");

        $this->addVars("autostart", !CGAF_DEBUG);

        $this->addVars("dock", "true");
        //$this->addVars ( "type", "video" );

        //$this->addVars("file", BASE_URL . "/media/get/?file=media.flv");


        //parameters
        $this->addParam("allowfullscreen", "true");
        $this->addParam("allowscriptaccess", (CGAF_DEBUG ? "always" : "sameDomain"));
        $this->addParam("expressInstall", "video");
        $this->addParam("quality", "autohigh");
        $this->addParam("wmode", "opaque");
    }

    function setCallback($o)
    {
        $this->_swfCallback = $o;
    }

    public function setWidth($width)
    {
        $this->_width = $width;
    }

    public function setHeight($value)
    {
        $this->_height = $value;
    }

    function addPlugin($pluginName)
    {
        $this->_plugins [] = array(
            'name' => $pluginName
        );
    }

    function addParam($paramName, $paramValue)
    {
        $this->_params->$paramName = $paramValue;
    }

    function addEvent($event, $f)
    {
        $this->_events [$event] [] = $f;
    }

    function addVars($varName, $paramValue = null)
    {
        if (is_array($varName) || is_object($varName)) {
            foreach ($varName as $k => $v) {
                $this->_vars [$k] = $v;
            }
        } else {
            $this->_vars [$varName] = $paramValue;
        }
    }

    function renderNoScript()
    {
        $id = $this->getId();
        $retval = "<object data=\"{$this->_swf}\" name=\"{$id}\" id=\"{$id}\"
				type=\"application/x-shockwave-flash\" width=\"{$this->_width}\" height=\"{$this->_height}\">";
        foreach ($this->_params as $k => $v) {
            $retval .= "<param  value=\"$v\" name=\"$k\">";
        }
        $plugin = array();
        if (count($this->_plugins)) {
            //TODO PluginConfig based on plugin name
            foreach ($this->_plugins as $p) {
                $plugin [] = $p ['name'];
            }
            $this->addVars("plugins", implode(",", $plugin));
        }

        $p = $this->_vars;
        $p ["plugin"] = implode(",", $plugin);

        $vars = "";
        foreach ($this->_vars as $k => $v) {
            $vars .= "$k=$v&amp;";
        }
        $vars = substr($vars, 0, strlen($vars) - 1);
        $retval .= "<param value=\"$vars\" name=\"flashvars\">";
        $retval .= "</object>";
        return $retval;
    }

    function Render($return = false)
    {
        $retval = null;
        $id = $this->getId();
        $template = $this->_appOwner->getTemplate();
        $js = "";
        if (!Request::isAJAXRequest()) {
            $template->addAsset("swfobject.js");
        } else {
            $js = "$.getScript('" . $template->getLive("swfobject.js") . "',function() {\n";
        }
        if (!Request::isSupport("javascript")) {
            $retval .= "<noscript>" . $this->renderNoScript() . "</noscript>";
        } else {
            $retval .= parent::Render(true); //'<div id="' . $this->getId () . '"></div>';
        }
        $plugin = array();
        if (count($this->_plugins)) {
            //TODO PluginConfig based on plugin name
            foreach ($this->_plugins as $p) {
                $plugin [] = $p ['name'];
            }
            $this->addVars("plugins", implode(",", $plugin));
        }
        $p = $this->_vars;
        $p ["plugin"] = implode(",", $plugin);

        $js .= "var flashvars=" . json_encode($p) . ";\n" . "var attributes={id:\"{$id}-object\",name:\"{$id}-object\"};\n" . "var params=" . json_encode($this->_params) . ";\n" . 'swfobject.embedSWF("' . $this->_swf . '","' . $id . '","' . $this->_width . '","' . $this->_height . '","' . $this->_flashVersion . '","' . $this->_flashInstallURL . '", flashvars,params, attributes' . ($this->_swfCallback ? ',' . $this->_swfCallback : '') . ');';
        if (!Request::isAJAXRequest()) {
            $template->addClientScript($js);
        } else {
            if (Request::isAJAXRequest()) {
                $js .= "});";
            }
            $retval .= $template->renderScript($js, true);
        }
        if (!$return) {
            Response::Write($retval);
        }
        return $retval;
    }
}

?>
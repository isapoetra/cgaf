<?php
namespace System\Web\UI\Ext;

use AppManager;
use Request;
use System\Web\JS\JSUtils;

class Control extends Component
{
    function __construct($class, $varPrefix = "g")
    {
        parent::__construct(null, $varPrefix);
        $this->_prefix = $varPrefix;
        $this->_class = $class;
    }

    function addChild($obj, $key = null)
    {
        $nparent = "Ext.getCmp('" . $this->getId() . "').getEl()";
        if (is_object($obj)) {
            //$obj->renderTo = $nparent;
            $obj->renderMode = 'parented';
            $obj->removeConfig("renderTo");
        } elseif (is_array($obj)) {
            $obj["renderTo"] = $nparent;
            $obj["renderMode"] = 'parented';
        }
        return parent::addItem($obj, $key);
    }

    function hasChildArray($obj)
    {
        if (!is_array($obj)) {
            return false;
        }
        foreach ($obj as $v) {
            if (is_array($v))
                return true;
        }
        return false;
    }

    function hasChildNotArray($obj)
    {
        if (!is_array($obj)) {
            return true;
        }
        foreach ($obj as $v) {
            if (is_array($v))
                return false;
        }
        return true;
    }

    function getTypeChilds($obj)
    {
        $retval = "";
        foreach ($obj as $v) {
            $retval[getType($v)] = getType($v);
        }
        return $retval;
    }

    function RenderConfigItems($items, $key = null)
    {
        $retval = array();
        if ($key) {
            foreach ($items as $k => $v) {
                $config = $this->toConfigJS($v);
                if (isset($this->_itemClass)) {
                    $retval[] = "new $this->_itemClass(" . $config . ")";
                } else {
                    $retval[] = (is_numeric($k) ? "" : "$k:") . $config;
                }
            }
        } else {
            if (!isset($this->_config[$key])) {
                return $retval;
            }
            $config = $this->toConfigJS($this->_config[$key]);
            if (isset($this->_itemClass)) {
                $retval[] = "new $this->_itemClass(" . $config . ")";
            } else {
                $retval[] = (is_numeric($key) ? "" : "$key:") . $config;
            }
        }
        //}
        $retval = "[" . implode(",\n", $retval) . "]";
        return $retval;
    }

    function prepareRender()
    {
        parent::prepareRender();
        if ($this->_renderMode == "parented") {
            $this->removeConfig("renderTo");
            if ($this->_parent) {
                if ($this->_js) {
                    foreach ($this->_js as $pos => $js) {
                        $this->_parent->addClientScript($js, $pos);
                    }
                    $this->_js = array();
                }
            }
        }
    }

    function prepareConfigItem($configType, &$itm)
    {
        switch (strtolower($configType)) {
            case "buttons":
                foreach ($itm as $k => $v) {
                    if (is_array($v)) {
                        $itm["$k"]["form"] = $this->id;
                    } else {
                        $v->form = $this->id;
                    }
                }
                break;
            default: /*if (isset($this->_itemClass[$configType])) {
				  $class = $this->_itemClass[$configType];
				 foreach ($itm as $k=>$v) {
				 $c = "new $class(".JSON::encodeConfig($v,$this->_strIgnore).")";
				 $itm[$k] = $c;
				 }
				 }*/
                break;
        }
    }

    function &addToLastConfig(&$config, $name, $val)
    {
        if (isset($config[$name])) {
            return $this->addToLastConfig($config[$name], $name, $val);
        }
        //make sure the last
        foreach ($config as $k => $v) {
            if (isset($v[$name])) {
                return $this->addToLastConfig($config[$k][$name], $name, $val);
            }
        }
        if (is_array($val)) {
            foreach ($val as $v) {
                $config[] = $v;
            }
        } else {
            $config[] = $val;
        }
        return $config;
    }

    function preRender()
    {
        parent::preRender();
        if (!$this->getConfig("renderTo") && $this->_renderMode != 'parented') {
            $this->setConfig("renderTo", Request::get("renderTo", new ExtJS("Ext.get('wrapper-content')")));
        }
        return "";
    }

    function renderConfigWidth($val)
    {
        if (is_numeric($val)) {
            return $val;
        }
        return "'$val'";
    }

    protected function getObjVarName()
    {
        return "o{$this->getId()}";
    }

    function RenderNoJSTag($return = false, $check = null)
    {
        if ($check === null) {
            $check = $this->_checkObjectInstance;
        }
        $owner = AppManager::getInstance();
        $pre = $this->preRender($return);
        if ($pre === false) {
            return false;
        }
        if (!is_bool($pre)) {
            $retval[] = $pre;
        }
        $hascontrol = false;
        if ($this->_controlScript && isset($this->_controlScript["url"])) {
            $this->_controlScript["url"] = $owner->getLiveData($this->_controlScript["url"], "js");
            if ($this->_controlScript["url"]) {
                $hascontrol = true;
                $retval[] = "GExt.loadScript('" . $this->_controlScript["id"] . "','" . $this->_controlScript["url"] . "',function() {";
            }
        }
        $varname = $this->getObjVarName();
        $obj = array();
        if ($check) {
            $obj[] = "$varname = Ext.getCmp('" . $this->id . "');";
            $obj[] = "if (typeof($varname) == 'undefined') {";
        }
        $obj[] = "$varname = new {$this->_class}(";
        $obj[] = $this->getJSONConfig();
        $obj[] = ");";
        $obj[] = $this->renderEvents($varname);
        if ($check) {
            $obj[] = "}else{cgaf.log($varname);}";
        }
        if (isset($this->_js["start"])) {
            //ppd($this->_js["start"]);
            foreach ($this->_js["start"] as $s) {
                if (!is_string($s) && is_object($s) && $s instanceof \IRenderable) {
                    $s = $s->Render(true);
                } elseif (!is_string($s)) {
                    ppd($s);
                }
                $retval[] = $s . ';' . (CGAF_DEBUG ? "\n" : '');
            }
        }
        $retval[] = join($obj, "\n");
        if (isset($this->_js["end"])) {
            $retval[] = join($this->_js["end"], ";\n");
        }
        if ($hascontrol) {
            $retval[] = "}," . (\CGAF::isDebugMode() ? "true" : "false") . ");";
        }
        $retval = implode("\n", $retval);
        if (!$return) {
            echo JSUtils::renderJSTag($retval, false);
        }
        return $retval;
    }

    function Render($return = false, &$handle = false)
    {
        //AppManager::setRenderForm ( false );
        if ($this->_renderMode === 'parented') {
            return parent::Render($return, $handle);
        }
        $retval = '';
        $script = $this->RenderNoJSTag(true);
        if (!Request::isAJAXRequest()) {
            AppManager::getInstance()->addClientScript($script);
            $retval .= $this->RenderHTML();
        } else {
            $retval = JSUtils::renderJSTag($script, false);
        }
        return $retval;
    }
}

<?php
namespace System\Web\UI\Ext;

use Request;
use Response;
use System\Exceptions\SystemException;
use System\JSON\JSON;
use System\Web\JS\JSUtils;
use Utils;

class Component extends \Control
{
    protected $_class;
    protected $_config;
    protected $_js;
    protected $_prefix = "g";
    protected $_htmlcontrol = "";
    protected $_itemClass;
    protected $_editable;
    protected $_controlScript;
    protected $_checkObjectInstance = true;
    protected $_parent = null;
    protected $_strIgnore = array("handler", "context", "scope", "plugins", "store", "data", "loader", "initComponent");
    protected $_baseURI;
    protected $_renderMode;
    protected $_addEditURL;
    protected $_deleteURL;
    protected $_viewURL;
    protected $_winConfig;
    protected $_reportURL;
    protected $_ignoreQuery = array("_task", "_a", "_dc");

    function __construct($config, $class = null, $varPrefix = "g")
    {
        $this->_strict = true;
        $this->_prefix = $varPrefix;
        $this->_class = $class;
        $this->setId(Utils::generateId($this->_prefix));
        if ($config !== null) {
            if (is_array($config)) {
                foreach ($config as $k => $v) {
                    $this->setConfig($k, $v);
                }
            }
        }
        parent::__construct();
    }

    function setParent($value)
    {
        $this->setRenderMode("parented");
        $this->_parent = $value;
    }

    function getParent()
    {
        return $this->_parent;
    }

    function setId($value)
    {
        //parent::setID ( $value );
        $this->setConfig("id", $value);
    }

    function setItems($value)
    {
        $this->_config["items"] = $value;
    }

    function getId()
    {
        return $this->getConfig("id");
    }

    function hasItem()
    {
        $cfg = $this->getConfig("items");
        if (!$cfg) {
            return false;
        }
        return count($cfg) > 0;
    }

    function addClientScript($script, $pos = "start")
    {
        if ($this->_renderMode == "parented" && $this->_parent) {
            $this->_parent->addClientScript($script, $pos);
            return;
        }
        if ($script instanceof ExtJS) {
            $script = $script->render(true);
        } elseif (is_array($script)) {
            foreach ($script as $s) {
                $this->addClientScript($s, $pos);
            }
            return;
        }
        $this->_js[$pos][] = $script;
    }

    function setReportURL($value)
    {
        $this->_reportURL = $value;
    }

    function getQueryString()
    {
        return Request::getIgnore($this->_ignoreQuery);
    }

    function addConfig($name, $value)
    {
        if (isset($this->_config[$name]) && is_array($this->_config[$name])) {
            $this->_config[$name][] = $value;
        }
    }

    function addTopBar($obj, $multi = true)
    {
        if (!isset($this->_config["tbar"])) {
            $this->_config["tbar"] = array();
        }
        if (is_array($obj)) {
            if ($multi) {
                foreach ($obj as $v) {
                    $this->_config["tbar"][] = $v;
                }
            } else {
                $this->_config["tbar"][] = $obj;
            }
        } else {
            $this->_config["tbar"][] = $obj;
        }
    }

    function addBottomBar($obj)
    {
        if (!isset($this->_config["bbar"])) {
            $this->_config["bbar"] = array();
        }
        foreach ($obj as $v) {
            $this->_config["bbar"][] = $v;
        }
    }

    function Initialize()
    {
        parent::Initialize();
        $minfo = \ModuleManager::getModuleInfo();
        $u = \Request::get("_u");
        $a = \Request::get("_a");
        if ($a !== null && $u == $a || ($minfo && (strtolower($a) == strtolower($minfo->mod_name)))) {
            $this->_ignoreQuery[] = "_a";
            $a = null;
        }
        $report = "&_a=" . ($a ? $a . "." : "") . "report";
        $this->_baseURI = $this->getQueryString($this->_ignoreQuery);
        $view = "&_a=" . ($a ? $a . "." : "") . "view";
        $edit = "&_a=" . ($a ? $a . "." : "") . "addedit";
        $delete = "&_dosql=" . ($a ? $a . "." : "") . "do_" . ($minfo ? $minfo->mod_dir : "") . ($u ? "_" . $u : "") . "_aed";
        $this->_viewURL = "./?" . Utils::arrayImplode($this->_baseURI, "=", "&") . $view;
        $this->_addEditURL = "./?" . Utils::arrayImplode($this->_baseURI, "=", "&") . $edit;
        $this->_deleteURL = "./?" . Utils::arrayImplode($this->_baseURI, "=", "&") . $delete;
        if (!$this->_reportURL) {
            $this->_reportURL = "./?" . Utils::arrayImplode($this->_baseURI, "=", "&") . $report;
        }
    }

    function setWinConfig($type, $config)
    {
        if (is_array($type)) {
            foreach ($type as $v) {
                $this->_winConfig[$v] = $config;
            }
        } else {
            $this->_winConfig[$type] = $config;
        }
    }

    function getWinConfig($type)
    {
        $config = null;
        if (isset($this->_winConfig[$type])) {
            $config = $this->_winConfig[$type];
        } elseif (isset($this->_winConfig["all"])) {
            $config = $this->_winConfig["all"];
        }
        if ($config) {
            if (is_array($config)) {
                if (!isset($config["title"])) {
                    $m = \ModuleManager::getModuleInfo();
                    $config["title"] = "'" . ucfirst($type) . " " . $m->mod_ui_name . "'";
                }
                $config = Utils::arrayImplode($config, ":", ",", '');
            }
        }
        return $config;
    }

    function addWinConfig($configName, $configVal)
    {
        $configName = explode(",", $configName);
        foreach ($configName as $v) {
            if (!isset($this->_winConfig[$v])) {
                $this->_winConfig[$v] = array();
            }
            if (is_array($configVal)) {
                $configVal = Utils::arrayMerge($this->_winConfig[$v], $configVal);
            } else {
                $configVal = array($configVal);
            }
            $this->_winConfig[$v] = $configVal;
        }
    }

    function setbaseUri($value)
    {
        $this->_baseURI = $value;
    }

    function setAddEditURL($value)
    {
        $this->_addEditURL = $value;
    }

    function setDeleteURL($value)
    {
        $this->_deleteURL = $value;
    }

    function setViewURL($value)
    {
        $this->_viewURL = $value;
    }

    function setClass($value)
    {
        $this->_class = $value;
    }

    function &getConfig($configName, $default = null)
    {
        $retval = isset($this->_config["$configName"]) ? $this->_config["$configName"] : $default;
        if ($retval === null && $default !== null) {
            $this->setConfig($configName, $default);
        }
        return $retval;
    }

    function removeConfig($configName)
    {
        // unset $array[$index], shifting others values
        $res = array();
        if (!$this->_config) {
            return;
        }
        foreach ($this->_config as $k => $v) {
            if ($k != $configName)
                $res[$k] = $v;
        }
        $this->_config = $res;
    }

    function __set($name, $value)
    {
        $var = get_class_vars(get_class($this));
        $method = "set$name";
        if (array_key_exists($name, $var)) {
            $this->$name = $value;
        } elseif (method_exists($this, $method)) {
            $this->$method($value);
        } else {
            $this->setConfig($name, $value);
        }
    }

    function __get($name)
    {
        $var = get_class_vars(get_class($this));
        $method = "get$name";
        if (array_key_exists($name, $var)) {
            return $this->$name;
        } elseif (method_exists($this, $method)) {
            return $this->$method();
        } else {
            return $this->getConfig($name, $name);
        }
    }

    function setEditable($value)
    {
        $this->_editable = true;
        $this->setConfig("editable", $value);
    }

    function setRenderMode($value)
    {
        $this->_renderMode = $value;
    }

    function getRenderMode()
    {
        return $this->_renderMode;
    }

    function getItemCount()
    {
        if (isset($this->_config["items"])) {
            return count($this->_config["items"]);
        }
        return 0;
    }

    function &getItems()
    {
        $item = null;
        if (isset($this->_config["items"])) {
            $item = $this->_config["items"];
        }
        return $item;
    }

    function &getItem($idx)
    {
        $item = null;
        if (isset($this->_config["items"]) && isset($this->_config["items"][$idx])) {
            $item = $this->_config["items"][$idx];
        }
        return $item;
    }

    function &addItem($obj, $key = null)
    {
        if (!isset($this->_config["items"])) {
            $this->_config["items"] = array();
        }
        $itm = & $this->_config["items"][];
        if ($key !== null && is_string($key)) {
            $itm = & $this->_config["items"][$key];
        }
        $itm = $obj;
        return $itm;
    }

    function addItems($arr)
    {
        foreach ($arr as $v) {
            $this->addItem($v);
        }
    }

    function getJSONConfig()
    {
        return JSON::encodeConfig($this->_config, $this->_strIgnore, $this->_itemClass);
    }

    function prepareConfigItem($configType, &$itm)
    {
        return null;
    }

    public function addIgnoreConfigStr($str)
    {
        if (!$str) {
            return false;
        }
        if (is_array($str)) {
            foreach ($str as $v) {
                $this->_strIgnore[] = $v;
            }
        } else {
            $this->_strIgnore[] = $str;
        }
        return true;
    }

    public function apply($configGroup, $configName, $value = null, $multipleValue = false)
    {
        $config = $this->getConfig($configGroup);
        if ($config) {
            if (is_object($config)) {
                $config->setConfig($configName, $value);
            } elseif (is_array($config)) {
                if ($multipleValue && is_array($configName)) {
                } else {
                    $config[$configName] = $value;
                }
            }
        }
        $this->setConfig($configGroup, $config);
        return $value;
    }

    protected function applyConfig($configName, $configValue, $checkMethod = true)
    {
        static $ignore;
        if ($checkMethod) {
            if (!$ignore) {
                $ignore = array("setid");
            }
            $method = "set$configName";
            if (!in_array(strtolower($method), $ignore) && is_callable(array($this, $method))) {
                $nval = call_user_func_array(array($this, "$method"), $configValue);
                $configValue = $nval !== null ? $nval : $configValue;
            }
        }
        $this->_config["$configName"] = $configValue;
        return $configValue;
    }

    function &setConfig($configname, $configValue = null, $ignoreexist = true, $checkMethod = false)
    {
        /**
         * @todo add Validation for  config Name
         */
        if (is_array($configname)) {
            foreach ($configname as $k => $v) {
                $this->applyConfig($k, $v, $checkMethod);
            }
        } else {
            if (!$ignoreexist) {
                if (isset($this->_config["$configname"]))
                    return $configValue;
            }
            $this->applyConfig($configname, $configValue, $checkMethod);
        }
        return $configValue;
    }

    function setConfigs($arr, $checkExist = true)
    {
        if (!$arr)
            return;
        foreach ($arr as $k => $v) {
            if ($checkExist && isset($this->_config["$k"])) {
                continue;
            }
            $this->_config["$k"] = $v;
        }
    }

    function addEvent($event, $handler)
    {
        if (!isset($this->_events[$event])) {
            $this->_events[$event] = array();
        }
        Utils::arrayMerge($this->_events[$event], $handler);
    }

    function prepareRender()
    {
        $items = $this->getConfig("items");
        if ($items) {
            foreach ($items as $item) {
                if ($item instanceof Component) {
                    $item->setParent($this);
                    $item->prepareRender();
                }
            }
        }
    }

    /**
     * Prepare Render...
     *
     * @return boolean
     */
    function preRender()
    {
        $this->_renderMode = Request::get("__mode", $this->_renderMode);
        if (count($this->_events) > 0) {
            $script = array();
            foreach ($this->_events as $k => $v) {
                if (is_string($v)) {
                    $script[] = "this.on('$k',$v)";
                } else {
                    foreach ($v as $e) {
                        $script[] = "this.on('$k',$e)";
                    }
                }
            }
            //Utils::array_unshift_assoc($this->_config,"initComponent","function(){".$this->_class.".superclass.initComponent.call(this);" . implode(";", $script) . "}");
        }
        $this->prepareRender();
        return "";
    }

    function renderEvents($varname = "obj")
    {
        if (count($this->_events) > 0) {
            $script = array();
            foreach ($this->_events as $k => $v) {
                $script[] = "$varname.on('$k',$v)";
            }
            return implode(";", $script);
            //Utils::array_unshift_assoc($this->_config,"initComponent","function(){".$this->_class.".superclass.initComponent.call(this);" . implode(";", $script) . "}");
        }
        return "";
    }

    function renderConfig()
    {
        $retval = "";
        if (!$this->_config) {
            return "";
        }
        foreach ($this->_config as $k => $v) {
            $handle = false;
            $this->prepareConfigItem($k, $v, $handle);
            if ($v === null) {
                continue;
            }
            $method = "renderConfig" . $k;
            if (is_string($v)) {
                $ignore = !in_array($k, $this->_strIgnore);
            } else {
                $ignore = $this->_strIgnore;
            }
            $res = JSON::encodeConfig($v, $ignore, array($this, "$method"));
            if ($res !== null) {
                $retval .= "$k:" . $res . ",";
            }
        }
        $retval = substr($retval, 0, strlen($retval) - 1);
        return $retval;
    }

    function RenderHTML()
    {
        $class = "\\System\\Web\\UI\\Controls\\WebControl";
        if ($this->_htmlcontrol) {
            $class = "\\System\\Web\\UI\\Controls\\" . str_replace(".", '\\', $this->_htmlcontrol);
            if (!class_exists($class, true)) {
                $class = "\\System\\Web\\UI\\Controls\\WebControl";
            }
        }
        /**
         *
         * Enter description here ...
         * @var \\System\\Web\\UI\\Controls\\WebControl
         */
        $ctl = new $class("html");
        $ctl->assign($this);
        return $ctl->Render(true);
    }

    function Render($return = false, &$handle = false)
    {
        $retval = $this->preRender();
        $retval .= $this->renderConfig();
        if ($this->_class) {
            $handle = true;
            $retval = "new $this->_class({" . $retval . "})";
        }
        if (!$return) {
            Response::Write($retval);
        }
        return $retval;
    }

    function renderVar($return = false, $varname = "obj")
    {
        $handle = false;
        $retval = "$varname=" . $this->Render(true, $handle) . ";";
        $retval .= $this->renderEvents($varname);
        if (!$return) {
            Response::Write($retval);
        }
        return $retval;
    }

    function getJS($pos)
    {
        return isset($this->_js["$pos"]) ? implode($this->_js["$pos"], ";\n") : "";
    }

    function setItemRenderTo($newid = null)
    {
        if (isset($this->_config["items"])) {
            foreach ($this->_config["items"] as $v) {
                $v->renderTo = $newid;
            }
        }
    }

    function RenderDirect($return = false)
    {
        if (Request::get("renderTo")) {
            $this->setConfigs(array("renderTo" => Request::get("renderTo")));
        }
        $handle = false;
        $ret = $this->Render(true, $handle);
        if (!$this instanceof Control) {
            $ret .= ";" . $this->renderEvents("obj");
            if (!$handle) {
                throw new SystemException("unknown class, cannot render Direct");
            }
            $ret .= $this->getJS("end");
            $ret = JSUtils::renderJSTag("var obj=" . $ret, false);
        }
        if (!$return) {
            Response::Write($ret);
        }
        return $ret;
    }
}

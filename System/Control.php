<?php
abstract class Control extends \BaseObject implements \IRenderable
{
    private $_properties;
    protected $_childs = array();
    private $_container;
    private $_parent;

    function __construct()
    {
        $this->_properties = array();
        $this->_events = array();
    }

    function getAppOwner()
    {
        return AppManager::getInstance();
    }

    function hasChild()
    {
        return count($this->_childs) > 0;
    }

    function setProperties($name, $value)
    {
        $this->_properties[$name] = $value;
    }

    function removeProperty($name)
    {
        unset($this->_properties [$name]);
    }

    function setProperty($propertyName, $value = null)
    {
        if (is_array($propertyName)) {
            foreach ($propertyName as $k => $v) {
                $this->_properties [$k] = $v;
            }
        } elseif ($propertyName) {
            $this->_properties [$propertyName] = $value;
        }
        return $this;
    }

    protected function setChilds($items)
    {
        $this->_childs = $items;
    }

    function &getChilds()
    {
        return $this->_childs;
    }

    protected function getProperties()
    {
        return $this->_properties;
    }

    function getProperty($name, $def = null)
    {
        return isset ($this->_properties [$name]) ? $this->_properties [$name] : $def;
    }

    function setParent($value)
    {
        $this->_parent = $value;
    }

    function getParent()
    {
        return $this->_parent;
    }

    function addChild($c)
    {
        if ($c === null) {
            return $this;
        }
        if (is_array($c)) {
            foreach ($c as $cc) {
                $this->addChild($cc);
            }
            return $c;
        }
        if ($c instanceof Control) {
            $c->setParent($this);
        }
        $this->_childs [] = $c;
        return $c;
    }

    protected function renderItems()
    {
        $retval = "";
        foreach ($this->_childs as $item) {
            if (is_object($item) && $item instanceof IRenderable) {
                $retval .= $item->render(true);
            } elseif (is_string($item)) {
                $retval .= $item;
            } elseif (CGAF_DEBUG) {
                $retval .= pp($item, true);
            }
        }
        return $retval;
    }

    public function renderChilds()
    {
        //$this->prepareRender ();
        return $this->renderItems();
    }

    protected function prepareRender()
    {
        if ($this->_container) {
            $this->_container->prepareRender();
        }
        foreach ($this->_childs as $item) {
            if (is_object($item) && $item instanceof IRenderable) {
                $item->prepareRender();
            }
        }
        //$this->preRender();
    }

    function Render($return = false)
    {
        $this->prepareRender();
        return $this->renderItems($return);
    }
}

?>

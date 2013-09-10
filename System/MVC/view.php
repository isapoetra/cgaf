<?php
namespace System\MVC;
abstract class View implements IView
{
    private $_controller;

    protected $_state;

    /**
     *
     * Enter description here ...
     * @param MVCController $controller
     */
    function __construct(Controller $controller)
    {
        $this->_controller = $controller;
    }

    function getAppOwner()
    {
        return $this->_controller->getAppOwner();
    }

    public abstract function display();

    function getState($stateName, $default = null)
    {
        if (!$this->_state) {
            return $this->_controller->getState($stateName, $default);
        }
        return $this->_state->getState($stateName, $default);
    }

    function setState($stateName, $value)
    {
        if (!$this->_state) {
            return $this->_controller->setState($stateName, $value);
        }
        return $this->_state->setState($stateName, $value);
    }

    /* (non-PHPdoc)
     * @see IRenderable::Render()
     */
    public function Render($return = false)
    {
        return $this->display();
    }

}

?>
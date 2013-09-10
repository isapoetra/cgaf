<?php
if (!defined("CGAF")) die("Restricted Access");

class JExtTaskbarButton extends HTMLControl
{

    function __construct()
    {
        parent::__construct("div");
    }
}

class JExtTaskButtonPanel extends HTMLControl
{

    function __construct()
    {
        parent::__construct("div");
    }
}

class JExtTaskbar extends HTMLToolbar
{
    protected $_id;
    protected $_iid;

    function __construct()
    {
        parent::__construct();
        $start = new JExtTaskbarButton();
        $this->addControl($start);
        $start = new JExtTaskButtonPanel();
        $this->addControl($start);
        /*	<div id="ux-taskbar">
            <div id="ux-taskbar-start"></div>
            <div id="ux-taskbuttons-panel"></div>
            <div class="x-clear"></div>
            </div>*/
    }

    function getButtonPanel()
    {
        return $this->_controls[1];
    }

    function getStartButton()
    {
        return $this->_controls[0];
    }

    function setID($value)
    {
        $this->_iid = $value;
        $this->_id = $value . "-taskbar";
    }

    function onBeforeRender($writer)
    {
        parent::onBeforeRender($writer);
        $this->startButton->ID = $this->ID . "-start";
        $this->ButtonPanel->ID = $this->_iid . "-taskbuttons-panel";
        $ctl = new HTMLControl("div");
        $ctl->cssClass = "x-clear";
        $this->addControl($ctl);
    }


}

?>
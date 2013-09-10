<?php
if (!defined("CGAF")) die("Restricted Access");
class JExtWizzard extends JExtForm
{
    const EXT_WIZZARD_TOP = 0;
    const EXT_WIZZARD_BOTTOM = 1;
    const EXT_WIZZARD_BOTH = 1;
    //protected $_currentStep = 0;
    protected $_state;
    protected $_maxStep;
    protected $_buttonPosition;
    protected $_titleStep = array();
    protected $_allowRestart = true;
    protected $_stateID;

    function __construct($stateID, $maxstep, $action = null)
    {
        $this->setMaxStep($maxstep);
        $this->_stateID = $stateID;
        $this->_state = cgaf::registerState($stateID);
        $this->_buttonPosition = self::EXT_WIZZARD_BOTH;
        parent::__construct($action);
        $this->_e = array(
            "onStep");
        $this->setConfig("frame", true, false);
    }

    function setState($stateName, $value)
    {
        return $this->_state->setState($stateName, $value);
    }

    function getState($stateName, $defvalue = null)
    {
        return $this->_state->getState($stateName, $defvalue);
    }

    protected function _getDefaultAction($ignore = null)
    {
        $ignore = array(
            "__currentStep");
        return parent::_getDefaultAction($ignore);
    }

    function getCurrentStep()
    {
        return $this->_state->getState("__currentStep", 0);
    }

    function setCurrentStep($value)
    {
        $old = $this->getCurrentStep();
        $this->_state->setState("__currentStep", $value);
        if ($this->hasEvent("onStep")) {
            if (!$this->raiseEvent("onStep", $this)) {
                $this->_state->setState("__currentStep", $old);
            }
        }
    }

    function setMaxStep($value)
    {
        $this->_maxStep = $value;
    }

    function setTitleStep($value)
    {
        $this->_titleStep = $value;
    }

    function getTitleStep()
    {
        return $this->_titleStep;
    }

    function getTitle($step)
    {
        if (!$this->_titleStep) return "";
        if (isset($this->_titleStep["$step"])) {
            return " " . __($this->_titleStep[$step]);
        }
        return "";
    }

    function Initialize()
    {
        parent::Initialize();
    }

    function preRender($return = false, $rendertoolbar = null)
    {
        $title = $this->getTitle($this->CurrentStep);
        $this->setConfig("title", "Step " . ($this->CurrentStep + 1) . " of " . ($this->_maxStep + 1) . ($title ? ": " . $title : ifdev(" : No Title Defined for step " . $this->CurrentStep . " !!!")), true);
        $nextstep = $this->CurrentStep >= $this->_maxStep ? $this->_maxStep : $this->CurrentStep + 1;
        $bar = array(
            array(
                "iconCls" => 'x-prev',
                "disabled" => $this->CurrentStep == 0 || $this->CurrentStep == $this->_maxStep,
                "text" => __("Previous") . ifdev($this->CurrentStep),
                "handler" => "function(frm,e) {" . $this->_getSubmitAction(false, array(
                    "__currentStep" => $this->CurrentStep - 1)) . "}"),
            array(
                "iconCls" => 'x-next',
                "text" => __(($this->CurrentStep >= $this->_maxStep ? "Finish" : "Next")) . ifdev($this->CurrentStep + 1),
                "handler" => "function(frm,e) {" . $this->_getSubmitAction(false, array(
                    "__currentStep" => $nextstep)) . "}"));
        //&& ($this->CurrentStep == $this->_maxStep)
        if ($this->_allowRestart) {
            $bar[] = array(
                "iconCls" => 'x-restart',
                "text" => __("Restart") . ifdev($this->CurrentStep),
                "handler" => "function(frm,e) {" . $this->_getSubmitAction(false, array(
                    "_restart" => 1,
                    "__currentStep" => 0)) . "}");
        }
        if ($this->_buttonPosition == self::EXT_WIZZARD_BOTTOM) {
            $this->addBottomBar($bar);
        } elseif ($this->_buttonPosition == self::EXT_WIZZARD_TOP) {
            $this->addTopBar($bar);
        } elseif ($this->_buttonPosition == self::EXT_WIZZARD_BOTH) {
            $this->addTopBar($bar);
            $this->addBottomBar($bar);
        }
        return parent::preRender($return, false);
    }

    function parseStep()
    {
        CGAF::setRenderProfile(false);
        if (isset($_REQUEST["_restart"])) {
            $this->_state = CGAF::unregisterState($this->_stateID, true);
            $this->CurrentStep = 0;
        }
        $step = CGAF::getParam("__currentStep");
        if ($step !== null) {
            AppModule::getAppOwner()->setRenderForm(false);
            Response::clear();
            $this->CurrentStep = $step;
            $response = new TJSONResponse();
            $response->redirectInternal = $this->_getActionUrl();
            $response->renderDirect();
            return true;
        }
        return false;
    }

    function Render($return = false)
    {
        if ($this->parseStep()) {
            return true;
        } else {
            //$this->raiseEvent("onStep", $this);
            return parent::Render($return);
        }
    }
}

?>
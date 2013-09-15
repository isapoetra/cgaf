<?php
namespace System\Web\UI\Controls;

use System\Web\UI\Controls\WebControl;
use System\Web\Utils\HTMLUtils;

class TabItem
{
    private $_title;
    private $_content;
    private $_active = false;
    private $_parent;
    private $_attrs;
    private $_actions;
    private $_ajax;

    function __construct($title, $content, $id = null, $attrs = null, $actions = null, $ajax = false)
    {
        $this->_id = $id ? $id : \Utils::generateId('tab');
        $this->_title = $title;
        $this->_content = $content;
        $this->_attrs = $attrs;
        $this->_actions = $actions;
        $this->_ajax = $ajax;
    }

    function setParent(Tab $tab)
    {
        $this->_parent = $tab;
    }

    function setId($id)
    {
        $this->_id = $id;
    }

    function setActive($value)
    {
        $this->_active = $value;
    }

    function renderTitle()
    {
        $attr = array('data-target' => ($this->_parent ? '#' . $this->_parent->getId() . ' ' : '') . '#' . $this->_id);
        \Utils::arrayMerge($attr, $this->_attrs);
        $retval = '<li class="tab-head ' . ($this->_active ? ' active' : '') . ($this->_actions ? ' dropdown ' : '') . '">';
        $retval .= '<span class="left">';
        $retval .= '<span class="right">';
        if ($this->_ajax) {

            $url = $this->_content . '#' . $this->_id;
            $this->_content = null;

            $retval .= '<a href="' . $url . '" data-toggle="tab" ' . HTMLUtils::renderAttr($attr) . '">';
        } else {
            $retval .= '<a href="#' . $this->_id . '" data-toggle="tab" ' . HTMLUtils::renderAttr($attr) . '">';
        }
        $retval .= '<span class="title">';
        $retval .= $this->_title;
        $retval .= '</span>';
        if ($this->_actions) {
            $retval .= '<a href="#" data-toggle="dropdown" class="dropdown-toggle">';
            $retval .= '<b class="caret"></b>';
            $retval .= '</a>';
        }
        $retval .= '</a>';
        if ($this->_actions) {
            $retval .= '<ul class="dropdown-menu">';
            foreach ($this->_actions as $a) {
                $retval .= '<li>' . \Convert::toString($a) . '</li>';
            }
            $retval .= '</ul>';
        }
        $retval .= '</span>';
        $retval .= '</span>';
        $retval .= '</li>';
        return $retval;

    }

    function renderContent()
    {
        return '<div class="tab-pane' . ($this->_active ? ' active' : '') . '" id="' . $this->_id . '">' . ($this->_content ? '<p>' . $this->_content . '</p>' : '') . '</div>';
    }
}

class Tab extends WebControl
{
    private $_tabs;
    private $_tabPosition = 'top';
    private $_ul;
    private $_content;
    private $_activeTab = null;

    function __construct($id = null)
    {
        parent::__construct('div');
        $this->setId($id);
        $this->_ul = new WebControl('ul', false, array('id' => 'tab' . $this->getId()));
        $this->_ul->setClass('nav nav-tabs');
        $this->_content = new WebControl('div', false, array('id' => 'ctab' . $this->getId()));
        $this->_content->setClass('tab-content');
        $this->setClass('tabbable');

    }

    function setActiveTab($idx)
    {
        $this->_activeTab = $idx;
    }

    function addTab($tab)
    {
        if (is_array($tab)) {
            $tmp = $tab;
            $tab = new TabItem($tmp['title'], @$tmp['content'], @$tmp['id'], @$tmp['attrs'], @$tmp['actions'], @$tmp['ajax']);
        }
        $this->_tabs[] = $tab;
    }

    function prepareRender()
    {
        if ($this->_activeTab === null) {
            $this->_activeTab = 0;
        }
        $script = <<< EOS
$('#{$this->getId()}').tab().bind('show', function(e) {
   var pattern=/#.+/gi //use regex to get anchor(==selector)
   var contentID = e.target.toString().match(pattern)[0]; //get anchor
   var url = e.target.toString().replace(contentID,'');
   if (url !== '' && url != document.URL && !$(contentID).html()) {
       url = cgaf.url(url,{
        __ajax : 1
       }).toString();
       var me =this;
       $(contentID).addClass('loading').load(url, function(){
           $(this).removeClass('loading');
           $(me).tab(); //reinitialize tabs
       });
   }
});
EOS;
        \AppManager::getInstance()->addClientScript($script);
        if (!isset($this->_tabs[$this->_activeTab])) {
            $this->_activeTab = 0;
        }
        foreach ($this->_tabs as $v) {
            $v->setParent($this);
            $v->setActive(false);
        }
        if (isset($this->_tabs[$this->_activeTab])) {
            $this->_tabs[$this->_activeTab]->setActive(true);
        }
        foreach ($this->_tabs as $id => $v) {
            $v->SetId('tb' . $id);
            $this->_ul->addChild($v->renderTitle());
        }
        $this->addChild($this->_ul);
        foreach ($this->_tabs as $v) {
            $this->_content->addChild($v->renderContent());
        }

        $this->addChild($this->_content);
        switch ($this->_tabPosition) {
            case 'top':
                break;
            case 'left':
                $this->addClass('tabs-left');
                break;
            case 'right':
                $this->addClass('tabs-right');
                break;
            case 'below':
            case 'bottom':
                $this->addClass('tabs-below');
        }
    }

}

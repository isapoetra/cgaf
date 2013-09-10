<?php
namespace System\Web\UI\Items;

use System\Web\UI\Controls\Anchor;
use System\Web\UI\Controls\WebControl;

class CarouselItem extends WebControl
{
    public $title;
    public $descr;
    public $subdescr;
    public $image;
    public $url;
    private $_selected = false;
    private $_customs = array();

    function __construct($arg = null)
    {
        parent::__construct('div', false);
        $this->setClass('item');
        if ($arg && is_array($arg)) {
            $this->image = $arg['image'];
            $this->title = $arg['title'];
            $this->descr = $arg['descr'];
        }
    }

    function setSelected($value)
    {
        if ($value) {
            $this->addClass('active');
        } else {
            $this->removeClass('active');
        }
    }

    function addCustom($e)
    {
        $this->_customs[] = $e;
    }

    function prepareRender()
    {
        parent::prepareRender();
        $url = null;
        if ($this->url) {
            $url = new Anchor($this->url, null);
        }
        $img = new WebControl('img', true);
        $img->setAttr('src', $this->image);
        if ($url) {
            $url->addChild($img);
            $this->addChild($url);
        } else {
            $this->addChild($img);
        }
        if ($this->title || $this->descr) {
            $c = new WebControl('div', false);
            $c->setClass('carousel-caption');
            if ($this->title) {
                $c->addChild('<h4>' . $this->title . '</h4>');
            }
            if ($this->descr) {
                $c->addChild('<div class="descr">' . $this->descr . '</div>');
            }
            if ($this->subdescr) {
                $this->addChild('<div class="subdescr">' . $this->subdescr . '</div>');
            }
            $this->addChild($c);
        }
        foreach ($this->_customs as $v) {
            $this->addChild($v);
        }
    }
}
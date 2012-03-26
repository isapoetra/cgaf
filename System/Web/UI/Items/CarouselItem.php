<?php
namespace System\Web\UI\Items;
use System\Web\UI\Controls\WebControl;

class CarouselItem extends WebControl{
	public $title;
	public $descr;
	public $image;
	private $_selected=false;
	function __construct($arg=null) {
		parent::__construct('div',false);
		$this->setClass('item');
		if ($arg && is_array($arg)) {
			$this->image =$arg['image'];
			$this->title=$arg['title'];
			$this->descr = $arg['descr'];
		}
	}
	function setSelected($value) {
		if ($value) {
			$this->addClass('active');			
		}else{
			$this->removeClass('active');
		}
	}
	function prepareRender() { 
		parent::prepareRender();
		$img = new WebControl('img',true);
		$img->setAttr('src',$this->image);
		$this->addChild($img);
		if ($this->title || $this->descr) {
			$c= new WebControl('div',false);
			$c->setClass('carousel-caption');
			if ($this->title) {
				$c->addChild('<h4>'.$this->title.'</h4>');
			}
			if ($this->descr) {
				$c->addChild('<div class="descr">'.$this->descr.'</div>');
			}
			$this->addChild($c);
		}
	}
}
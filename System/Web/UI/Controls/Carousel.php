<?php
namespace System\Web\UI\Controls;

use System\Web\UI\JQ\Control;

use System\Web\UI\Items\CarouselItem;

use System\Web\UI\Controls\WebControl;
/**
 * <div id="myCarousel" class="carousel">
 <!-- Carousel items -->
 <div class="carousel-inner">
 <div class="active item">…</div>
 <div class="item">
 <img src=""/>
 <div class="carousel-caption">
 </div>

 </div>
 <div class="item">bbbbbbbbbbbbbbb</div>
 </div>
 <!-- Carousel nav -->
 <a class="carousel-control left" href="#myCarousel"
 data-slide="prev">&lsaquo;</a> <a class="carousel-control right"
 href="#myCarousel" data-slide="next">&rsaquo;</a>
 </div>
 */
class Carousel extends Control {
	private $_inner;
	private $_control;
	private $_selectedIndex = 0;
	private $_customs = array();
	function __construct() {

		parent::__construct(null, 'carousel');
		//'div',false,array('class'=>'carousel slide'));
		$this->setClass('carousel slide');
		$this->_inner = new WebControl('div', false, array('class' => 'carousel-inner'));

	}
	function addCustom($c) {
		$this->_customs[] = $c;
	}
	function add($c) {
		if (is_array($c)) {
			$tmp = $c;
			$c = new CarouselItem($tmp);
		}
		$this->_inner->addChild($c);

		return $this;
	}
	function setSelected($index) {
		$this->_selectedIndex = $index;
	}
	function prepareRender() {
		if ($this->_renderPrepared) {
			return;
		}
		parent::prepareRender();
		$this->_control = '<a class="carousel-control left" href="#' . $this->getId() . '" data-slide="prev"><img src="/asset/images/nav-left.png"/></a>' . '<a class="carousel-control right" href="#' . $this->getId() . '" data-slide="next"><img src="/asset/images/nav-right.png"/></a>';
		if ($this->_customs) {
			$this->_control .= '<div class="custom">' . \Convert::toString($this->_customs) . '</div>';
		}
		$ch = $this->_inner->getChilds();
		foreach ($ch as $k => $v) {
			$ch[$k]->setSelected(false);
		}
		if (isset($ch[$this->_selectedIndex])) {
			$ch[$this->_selectedIndex]->setSelected(true);
		}

		$this->addChild($this->_inner);
		$this->addChild($this->_control);
	}
}

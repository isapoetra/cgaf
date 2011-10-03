<?php
namespace System\Web\UI\Controls;
class ImageScroll extends WebControl {
	private $_itemClass = "img-item";
	function __construct($items) {
		parent::__construct('div', false, 'class="image-scroll fill-parent"');
		$container = new WebControl('div', false, array(
				'class' => 'img-container fill-parent'));
		$this->addChild($container);
		$cnav = new WebControl('div', false);
		$cnav->addChild(new WebControl('a', false, 'class="left-nav";href="#";title="Prev"'));
		$cnav->addChild(new WebControl('a', false, 'class="right-nav";href="#";title="Next"'));
		$this->addChild($cnav);
		$this->_container = $container;
		$this->addChild($items);
	}
	function addChild($c) {
		if (is_array($c)) {
			foreach ($c as $cc) {
				$this->addChild($cc);
			}
			return $c;
		}
		if ($this->_container) {
			if (is_object($c)) {
				$c->addStyle($this->_itemClass);
			}elseif (is_string($c)) {
				$c = '<div class="'.$this->_itemClass.'">'.$c.'</div>';
			}
			return $this->_container->addChild($c);
		}
		return parent::addChild($c);
	}
	function renderChilds() {
		$retval = '<div>';
		$retval .= parent::renderChilds();
		$retval .= '</div>';
		return $retval;
	}
	function prepareRender() {
		CGAFJS::loadPlugin('image-scroll', true);
		$id = $this->getId();
		$sc = <<< EOF
$('#$id').imgscroll({});
EOF;
		$this->getAppOwner()->addClientScript($sc);
		$this->getAppOwner()->addClientAsset($this->getAppOwner()->getLiveAsset('image-scroll.css', 'cgaf'));
		return parent::prepareRender();
	}
}

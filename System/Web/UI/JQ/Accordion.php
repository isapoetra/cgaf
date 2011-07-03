<?php
defined("CGAF") or die("Restricted Access");
class TJQAccordionItem extends Object implements IRenderable {
	private $_items =  array();
	function __construct($title,$content) {
		parent::__construct();
		$this->Title=$title;
		$this->Content = $content;
	}
	function addItem($item) {
		$this->_items[] = $item;
		return $this;
	}
	function Render($return=false) {
		$r = '<h3 class="'.$this->Class.'"><a href="#">'.$this->Title.'</a></h3>';
		$r .= '<div>'.$this->Content;
		if (count($this->_items)) {
			$r .= '<ul>';
			foreach($this->_items as $v) {
				$r .= '<li>'.(is_object($v) ? $v->render(true) : $v).'</li>';
			}
			$r .= '</ul>';
		}
		$r .= '</div>';
		if (!$return) {
			Response::write($r);
		}

		return $r;
	}
}
class TJQAccordion extends JQControl {
	private $_divAttr;
	function __construct($id,$template,$items=array()) {
		parent::__construct($id,$template);
		$this->setChilds($items ? $items : array());
	}
	function setDivAttr($attr) {
		$this->_divAttr =$attr;
	}
	/**
	 *
	 * @param $title
	 * @param $content
	 * @return TJQAccordionItem
	 */
	function AddAccordion($title,$content=null) {
		$item = new TJQAccordionItem($title,$content);
		parent::add($item);
		return $item;
	}
	private function _render($o) {
		if ($o ==null) {
			return  "";
		}elseif(is_string($o)) {
			return $o;
		}
	}

	function _renderStyle($st) {

	}
	function prepareRender() {
		$this->setConfig(array(
			'fillspace'=>true
		),null,false);
	}
	function RenderScript($return = false) {

		$id = $this->getId();

		$items = $this->getChilds();
		$this->getTemplate()
			->addClientScript('$("#'.$id.'").accordion('.JSON::encodeConfig($this->_configs).');');
		$r = '<div id="'.$id.'" '.HTMLUtils::renderAttr($this->_divAttr).' >';
		$i=0;
		foreach ($items as $a) {
			$class = ($i==0 ? "current" : "");
			if ($a instanceof TJQAccordionItem) {
				$a->Class = $class;
				$r .=  $a->Render(true);
			}elseif (is_array($a)) {
				$r .= '<h3 class="'.$class.'"><a href="#">'.$a["title"].'</a></h3>';
				$r .= '<div>'.$this->_render($a['content']).'</div>';
			}
			$i++;
		}
		$r .= '</div>';
		if (!$return) {
			Response::write($r);
		}
		return $r;
	}

}
?>

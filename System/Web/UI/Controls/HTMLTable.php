<?php
namespace System\Web\UI\Controls;

class HTMLTable extends WebControl {
	private $_url;
	private $_model;
	function __construct($attr = null) {
		parent::__construct('table', false, $attr);
	}

	public function &newRow($cols = null) {
		$row = new HtmlTableRow();
		$row = $this->addChild($row);
		if (is_array($cols)) {
			foreach ($cols as $v) {
				$row->newCol($v);
			}
		}
		return $row;
	}
	function addHeader($arrHead) {
		$row = $this->newRow();
		foreach ($arrHead as $v) {
			$col = $row->newCol($v);
			$col->header = true;
		}
	}
	private function lastChild() {
		$c = $this->getChilds();
		if (count($c) == 0) {
			$this->newRow();
			$c = $this->getChilds();
		}
		return $c[count($c) - 1];
	}
}

class HtmlTableCol extends HTMLControl {

	function __construct() {
		parent::__construct("td");
		$this->vAlign = 'middle';
	}

	function setVAlign($value) {
		$this->setAttr("valign", $value);
	}

	function setHeader($value) {
		$this->_tagName = ($value ? "th" : "td");
	}

	function setWidth($value) {
		$this->setAttr("width", $value);
	}

	function getColSpan() {
		return $this->getAttr("colspan");
	}

	function setColSpan($value) {
		$this->setAttr("colspan", $value);
	}

	function getRowSpan() {
		return $this->getAttr("rowspan");
	}

	function setAlign($value) {
		$this->setAttr("align", $value);
	}

	function setRowSpan($value) {
		$this->setAttr("rowspan", $value);
	}

	function setNowrap($value) {
		$this->setAttr("nowrap", $value);
	}
}

class HtmlTableRow extends HTMLControl {

	function __construct() {
		parent::__construct("tr");
	}

	/**
	 * Enter description here...
	 *
	 * @return THtmlTableCol
	 */
	function &newCol($caption = null) {
		$col = new HtmlTableCol();
		if (is_object($caption)) {
			$col->addChild($caption);
		} else {
			$col->setText($caption);
		}
		$this->addChild($col);
		return $col;
	}
}

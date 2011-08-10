<?php
namespace System\Web\UI\JQ;
class ReportToolbar extends Toolbar {
	private $_report;

	function __construct($report, $id = null) {
		parent::__construct($id);
		$this->setAttr(array (
				'class' => 'report-toolbar'
		));
		$this->_report = $report;
	}

	function prepareRender() {
		if ($this->_renderPrepared) {
			return;
		}
		$pi = $this->_report->getInfo();
		$id = $this->getId();
		$this->addChild(HTMLUtils::renderButton('button', 'Preview', 'preview', array (
				'id' => $id . '-preview',
				'class' => 'preview'
		), false, 'report/preview.png'));
		$this->addSeparator();
		$this->addChild(HTMLUtils::renderButton('button', 'First', 'first', array (
				'id' => $id . '-first',
				'class' => 'last'
		), false, 'report/first.png'));
		$this->addChild(HTMLUtils::renderButton('button', 'Previous', 'prev', array (
				'id' => $id . '-prev',
				'class' => 'prev'
		), false, 'report/prev.png'));
		$this->addChild('<span class="goto-page">Page<input type="textbox" id="' . $this->getId() . '-goto-page" value="' . ($pi->currentPage+1) . '" class="goto-page"/> of ' . ($pi->totalPage+1) . '</span>');
		$this->addChild(HTMLUtils::renderButton('button', 'Next', 'next', array (
				'id' => $id . '-next',
				'class' => 'next'
		), false, 'report/next.png'));
		$this->addChild(HTMLUtils::renderButton('button', 'Last', 'prev', array (
				'id' => $id . '-last',
				'class' => 'last'
		), false, 'report/last.png'));
		return parent::prepareRender();
	}
}
<?php
namespace System\Web\UI\JQ;
use System\JSON\JSON;

use System\MVC\MVCHelper;

use System\Web\UI\Controls\WebControl;

class Report extends Control {
	private $_toolbar;
	private $_model;
	private $_reportPageTemplate = "report/page";
	private $_info;
	private $_baseLang;
	private $_controller;

	function __construct($model, $controller, $config = null) {
		parent::__construct(null, null);
		$this->setAttr(array (
				'class' => 'report-container'
		));
		$this->_model = $model;
		$this->setConfig($config);
		$this->_controller = $controller;

		$this->_configs->merge(array (
				'reportTitle' => 'Report',
				'pageTitle' => 'Page Title',
				'ui' => array (
						'showtoolbar' => true,
						'displayfooter' => true,
						'displayreportheader' => true
				)
		), false);
		$this->_toolbar = new ReportToolbar($this);
	}

	function setModel($model) {
		$this->_model = $model;
		$this->_info - null;
	}

	function getInfo() {
		if (! $this->_info) {
			$pi = new \stdClass();
			$rpp = \Request::get('__rpp', $this->getConfig('rowperpage', 20));
			$cp = \Request::get('__cp', 0);
			if ($cp < 0) {
				$cp = 0;
			}
			$pi->rowPerPage = $rpp;
			$pi->currentPage = $cp;
			$pi->rowCount = ( int ) $this->_model->getRowCount();
			$pi->totalPage = round($pi->rowCount / $rpp) - 1;
			$pi->totalPage = $pi->totalPage < 0 ? 1 : $pi->totalPage;
			//ppd(round($pi->rowCount / $rpp));
			$this->_info = $pi;
		}

		return $this->_info;
	}

	public function _($txt, $def = null) {
		return __(($this->_baseLang ? $this->_baseLang . '.' : '') . $txt, $def);
	}

	function prepareRender() {
		if (! \Request::isDataRequest()) {
			$tpl = $this->getTemplate();
			$tpl->addAsset('report.xml');
			$this->_toolbar->setId($this->getId() . '-toolbar');
			if ($this->getConfig('ui.showtoolbar')) {
				$this->addChild($this->_toolbar);
			}
			$pi = $this->getInfo();

			$pc = new WebControl('div', false, array (
					'class' => 'page-container',
					'id' => $this->getId() . '-page-container'
			));

			if (\Request::get('__preview', false)) {
				for($i = 0; $i < $pi->pageCount; $i ++) {
					$p = new WebControl('div', false, array (
							'class' => 'report-page'
					));
					$pc->addChild($p);
				}
			} else {
				$p = new WebControl('div', false, array (
						'id' => $this->getId() . '-report-page',
						'class' => 'report-page'
				));
				$pc->addChild($p);
				$url = MVCHelper::getRouteORI(null);
				$url = \URLHelper::addParam($url, array (
						'__cp' => $pi->currentPage - 1,
						'__data' => 1
				));
				$tpl->addClientScript('$(\'#' . $p->getId() . '\').load(\'' . $url . '\')');

				$tpl->addClientScript('$(\'#' . $this->getId() . '\').webReport(' . JSON::encodeConfig($pi) . ');');

			}
			$this->addChild($pc);
		}
		return parent::prepareRender();
	}

	function setBaseLang($lang) {
		$this->_baseLang = $lang;
	}

	function renderPageHeader($rows, $page) {
		$r = $this->getConfig('pageHeader-' . $page, $this->getConfig('pageHeader'));
		if (! $r) {
			$r .= '<tr class="data-header">';
			foreach ( $rows as $k => $v ) {
				if (!is_numeric($k)) {
					$r .= '<th>' . $this->_($k) . '</th>';
				}else{
					$r .= '<th>' . $this->_($v) . '</th>';
				}
			}
			$r .= '</tr>';
		}
		return $r;
	}

	function RenderScript($return = false) {

		return parent::RenderScript($return);
	}

	private function renderPage($page, $rowPerPage, $reportHeader=null) {
		$pi = $this->getInfo();
		$rows = $this->_model->loadObjects(null, $page, $rowPerPage);
		if (!isset($rows[0])) {
			return '';
		}
		$fields = $this->getConfig('fields', $rows[0]);

		$pageClass = 'a4';
		$retval = '<div class="page-inner ' . $pageClass . '">';
		$reportHeader = $page == 0 && $this->getConfig('ui.displayreportheader');
		if ($reportHeader) {
			$retval .= '<div class="report-header">' . $this->getConfig('reportTitle') . '</div>';
		}
		$retval .= '<div class="content">';
		$retval .= '<table cellspacing="0" cellpadding="0" align="center" border="1">';
		$retval .= $this->renderPageHeader($fields, $page);
		foreach ( $rows as $k => $v ) {
			$retval .= '<tr>';
			$handle = false;
			if ($this->_controller) {
				$handle = $this->_controller->parseReportRow($v, $this);
				if ($handle) {
					$retval .= $handle;
				}
			}
			if (! $handle) {
				foreach ( $v as $val ) {
					$retval .= '<td nowrap="nowrap">' . $val . '</td>';
				}
			}
			$retval .= '</tr>';
		}

		$retval .= '</table>';
		$retval .= '</div>';
		if ($this->getConfig('ui.displayfooter')) {
			$retval .= '<div class="page-footer">' . $this->getConfig('footer', sprintf($this->_('pageoftitle', 'Page %d of %d'), $page + 1, $pi->totalPage + 1)) . '</div>';
		}
		$retval .= '</div>';
		return $retval;
	}

	function renderJSON($return = true) {
		$pi = $this->getInfo();
		$preview = \Request::get('__preview');
		if (! $preview) {
			$retval = $this->renderPage($pi->currentPage, $pi->rowPerPage);

		} else {

			$retval = '<div class="report-preview">';
			for($i = 0; $i < $pi->totalPage + 1; $i ++) {
				$retval .= $this->renderPage($i, $pi->rowPerPage);
			}
			$retval .= '</div>';
		}
		return $retval;
		/*return $this->getTemplate ()->render ( $this->_reportPageTemplate, true, false, array (
				'report' => $this,
				'rows' => $rows
		) );*/
	}
}
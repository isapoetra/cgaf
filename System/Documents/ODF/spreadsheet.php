<?php
namespace System\Documents\ODF;
use System\Documents\ODF\Dio\Table;
use System\Documents\ZipArchive;
use System\Documents\ODF\Dio\Archive;
use System\Documents\ODF\Dio\Text\A;
use System\Documents\ODF\Dio\Text\P;
use System\Documents\ODF\Dio\Document;
use System\Documents\ODF\Dio\Document\Content;
use System\Documents\ODF;
class SpreadSheet extends Archive {
	private $_sheets = array();
	function __construct($f = null) {
		parent::__construct(Document::TYPE_SPREADSHEET);
		$this->_filename = $f;
		if (is_file($f)) {
			$r = parent::open($f);
			if ($f) {
				$content = $this->getFromName('content.xml');
				$this->loadcontent($content);
			}
		}
	}
	private function loadContent($content) {
		$this->_sheets = array();
		$this->_content->loadXML($content);
		$list = $this->_content->body->getElementsByTagName('spreadsheet')->item(0)->getElementsByTagName('table');
		foreach ($list as $item) {
			$this->_sheets[$item->getAttribute('table:name')] = $item;
		}
	}
	/**
	 *
	 * Enter description here ...
	 * @param string $index
	 * @return System\Documents\ODF\Dio\Table
	 */
	private function &_findSheet($index) {
		if (isset($this->_sheets[$index])) {
			return $this->_sheets[$index];
		}
		$this->_sheets[$index] = $this->content->addTable($index);
		$this->_sheets[$index]->setAttribute('table:name', $index);
		return $this->_sheets[$index];
	}
	function cell($sheet, $row, $cell, $value = null, $type = 'string') {
		if (is_numeric($sheet)) {
			$sheet = 'Sheet ' . ($sheet + 1);
		}
		$sname =$sheet;
		$sheet = $this->_findSheet($sheet);
		if ($value === null) {
			if ($sheet instanceof Table) {
				$x = $sheet->get($cell, $row);
				$value = $x->textContent;
			} else {
				$r = $sheet->getElementsByTagName('table-row')->item($row);
				if ($r) {
					$c = $r->getElementsByTagName('table-cell')->item($cell);
					if ($c) {
						$value = $c->textContent;
					}
				}
			}
		} else {
			if (is_string($value)) {
				$value = new P($value);
			}
			$x = $sheet->put($cell, $row, $value);
		}
		return $value;
	}
	function save($f) {
		$this->open($f, ZipArchive::OVERWRITE);
		$this->addEmptyDir('META-INF');
		$r = $this->render();
		file_put_contents($f, $r);
		return $f;
	}
}

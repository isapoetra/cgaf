<?php
class TSearchItemCollection extends TCollection {
	private $_resultCount=0;
	public function add($item, $multi = false) {
		if ($item instanceof TSearchItem) {
			return parent::add ( $item, false );
		}
	}
	function setResultCount($value) {
		$this->_resultCount = $value;
	}
	function getResultCount() {
		return $this->_resultCount;
	}
	function renderAs($f) {
		$retval = '';
		switch ($f) {
			case 'html':
				foreach($this as $v) {
					$retval .=  $v->renderAs($f);
				}
			break;
			default:
				throw new SystemException('unhandle output format '.$f);
			break;
		}
		return $retval;

	}
}
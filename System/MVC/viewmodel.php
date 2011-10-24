<?php
namespace System\MVC;
class ViewModel extends Model {
	function __construct($connection,$viewName) {
		parent::__construct($connection,$viewName);
		$this->_isExpr=true;
		$this->clear();
	}
	function store($throw = true) {
		throw new SystemException('view is readonly');
	}
}
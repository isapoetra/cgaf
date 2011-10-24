<?php
namespace System\Controllers;
use System\MVC\Controller;
class personContactController extends Controller {
	function Initialize() {
		if (parent::Initialize()) {
			$this->setModel('persondetail');
			return true;
		}
		return false;
	}
}

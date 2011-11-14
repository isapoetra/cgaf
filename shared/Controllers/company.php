<?php
namespace System\Controllers;
use System\Exceptions\InvalidOperationException;
use System\Exceptions\SystemException;
use System\MVC\Controller;
class CompanyController extends Controller {
	function isAllow($access = 'view') {
		switch ($access) {
		case 'view':
		case 'profile':
			return $this->isFromHome();
			break;
		default:
			;
			break;
		}
		return parent::isAllow($access);
	}
	function Initialize() {
		if (parent::Initialize()) {
			$this->setModel('companies');
			return true;
		}
		return false;
	}
	function getCompanyLogo($row) {
		$def = $this->getLiveData('company/no-logo.png');
		if (!$row) {
			return $def;
		}
		$retval = null;
		if ($row->company_logo) {
			$retval = $this->getLiveData($row->company_logo);
			if (!$retval) {
				$retval = $this->getLiveData('company/' . $row->company_logo);
			}
		}
		if (!$retval) {
			$retval = $this->getLiveData('company/' . $row->company_id . '.png');
		}
		return $retval ? $retval : $def;
	}
	function profile() {
		$row = $this->getModel()->reset()->load(\Request::get('id'));
		if (!$row) {
			throw new InvalidOperationException('Invalid ID');
		}
		$this->getAppOwner()->assign('title', $row->company_name);
		return parent::renderView(__FUNCTION__, array(
				'mode' => \Request::get('mode', 'full'),
				'row' => $row));
	}
}

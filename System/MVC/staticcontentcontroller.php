<?php
namespace System\MVC;
use System\Web\Utils\HTMLUtils;
use System\Template\TemplateHelper;
abstract class StaticContentController extends Controller {
	protected function getParamForAction($a) {
		$params = $this->getAppOwner()->getConfigs('controllers.' . $this->getControllerName() . '.' . $a, array());
		$params['asseturl'] = BASE_URL . 'asset/get/?appId=' . $this->getAppOwner()->getAppId() . '&q=';
		return $params;
	}
	protected function getContentFile($a, $check = true) {
		$f = $this->getAppOwner()->getInternalData('data/' . $this->getControllerName() . '/', true) . $a . '.html';
		if ($check) {
			if (is_file($f)) {
				return $f;
			}
			return null;
		}
		return $f;
	}
	protected function getActions($a) {
	}
	function Index() {
		$route = MVCHelper::getRoute();
		$a = $route['_a'];
		switch (strtolower($a)) {
		case 'index':
			return parent::Index();
		default:
			$f = $this->getContentFile($a);
			if (is_file($f)) {
				$action = $this->getActions($a);
				$retval = '';
				if ($action) {
					$retval = HTMLUtils::renderLinks($action, array(
							'class' => 'actions ' . $a . '-actions'));
				}
				$retval .= TemplateHelper::renderFile($f, $this->getParamForAction($a), $this);
				return $retval;
			} else {
				return parent::render();
			}
		}
		return parent::Index();
	}
}

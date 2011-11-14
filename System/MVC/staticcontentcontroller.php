<?php
namespace System\MVC;
use System\ACL\ACLHelper;
use System\Exceptions\SystemException;
use System\Web\Utils\HTMLUtils;
use System\Template\TemplateHelper;
abstract class StaticContentController extends Controller {
	protected $_template;
	protected $_useLocale = true;
	protected function getParamForAction($a, $mode = null) {
		$params = $this->getAppOwner()->getConfigs('controllers.' . $this->getControllerName() . '.' . $a, array());
		$params['asseturl'] = BASE_URL . 'asset/get/?appId=' . $this->getAppOwner()->getAppId() . '&q=';
		$params['baseurl'] = BASE_URL;
		$params['appurl'] = APP_URL;
		return $params;
	}
	public function getContentPath() {
		return $this->getAppOwner()->getInternalData('data/' . $this->getControllerName() . '/', true);
	}
	protected function getContentFile($a, $check = true) {
		$spath = array(
				$this->getContentPath(),
				\CGAF::getInternalStorage('data/' . $this->getControllerName() . '/', false));
		$lc = $this->getAppOwner()->getLocale()->getLocale();
		$dc = $this->getAppOwner()->getLocale()->getDefaultLocale();
		foreach ($spath as $p) {
			$def = $p . $a . '.html';
			if ($this->_useLocale) {
				$f = $p . $a . '-' . $lc . '.html';
			}
			if (!$check) {
				if (is_file($f)) {
					return $f;
				} elseif ($lc === $dc) {
					return $def;
				} else {
					return $f;
				}
			} else {
				if (is_file($f)) {
					return $f;
				} elseif (is_file($def)) {
					return $def;
				}
			}
		}
	}
	protected function renderFile($a, $f) {
		$params = $this->getParamForAction($a);
		$action = $this->getActions($a);
		$retval = '';
		if ($action) {
			$retval = HTMLUtils::renderLinks($action, array(
					'class' => 'actions ' . $a . '-actions'));
		}
		if (is_file($f) && is_readable($f)) {
			$params['content'] = TemplateHelper::renderString(file_get_contents($f), $params, $this, \Utils::getFileExt($f, false));
		} else {
			$params['content'] = 'content file not found ' . ($this->getAppOwner()->isDebugMode() ? $f : '');
		}
		$tpl = $this->_template ? $this->getFile($this->getControllerName(), $this->_template, 'Views') : null;
		$retval .= $tpl ? TemplateHelper::renderFile($tpl, $params, $this) : $params['content'];
		return $retval;
	}
	protected function getActions($a) {
	}
	function Index($a = null) {
		$route = MVCHelper::getRoute();
		$a = $a ? $a : $route['_a'];
		switch (strtolower($a)) {
		case 'index':
			return parent::Index();
		default:
			$id = ACLHelper::secureFile(\Request::get('id'), false);
			if ($id) {
				$a = $a . DS . $id;
			}
			$f = $this->getContentFile($a, false);
			return $this->renderFile($a, $f);
			if (is_file($f)) {
			} else {
				if (CGAF_DEBUG) {
					throw new SystemException('content file not found' . $f);
				}
				return parent::render();
			}
		}
		return parent::Index();
	}
}

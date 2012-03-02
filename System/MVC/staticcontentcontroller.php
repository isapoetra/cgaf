<?php
namespace System\MVC;
use System\ACL\ACLHelper;
use System\Exceptions\SystemException;
use System\Web\Utils\HTMLUtils;
use System\Template\TemplateHelper;

abstract class StaticContentController extends Controller {
	protected $_template;
	protected $_useLocale = true;
	/**
	 *
	 * Extension of content file without dot
	 * @var string
	 */
	protected $_fileExt = 'html';
	public function getContentPath() {
		return $this->getAppOwner()->getInternalData('contents/' . $this->getControllerName() . '/', true);
	}
	protected function getContentFile($a, $check = true) {
		$spath = array(
				$this->getContentPath(),
				\CGAF::getInternalStorage('contents/' . $this->getControllerName() . '/', false)
		);
		$lc = $this->getAppOwner()->getLocale()->getLocale();
		$dc = $this->getAppOwner()->getLocale()->getDefaultLocale();
		$s = array();
		foreach ($spath as $p) {
			$def = $p . $a . '.' . $this->_fileExt;
			if ($this->_useLocale) {
				$f = $p . $a . '-' . $lc . '.' . $this->_fileExt;
				$s[] = $f;
			}
			$s[] = $def;
			if (!$check) {
				if (is_file($f)) {
					return $f;
				} elseif ($lc === $dc) {
					return $def;
				}
			} elseif (is_file($f)) {
					return $f;
			}elseif (is_file($def)) {
				return $def;
			}

		}
		ppd($s);
	}
	/**
	 * Enter description here ...
	 * @param string $a
	 * @param string $f
	 * @return NULL
	 */
	protected function renderFile($a, $f) {
		return $this->renderStaticConent($a, $f, $this->_template);
	}
	/* (non-PHPdoc)
	 * @see System\MVC.Controller::Index()
	 */
	function Index($a = null) {
		$route = MVCHelper::getRoute();
		$a = $a ? $a : $route['_a'];
		/*switch (strtolower($a)) {
		case 'index':
		    return parent::Index();*/
		//default:
		$id = ACLHelper::secureFile(\Request::get('id',null,true), false);
		if ($id) {
			$a = $a . DS . $id;
		}
		$f = $this->getContentFile($a, true);
		
		if ($f && is_file($f)) {
			
			return $this->renderFile($a, $f);
		} elseif ($route['_a'] !=='index') {
		
			return "Content not found";
		} elseif (CGAF_DEBUG) {
			pp('content file not found' . $a);
		}
		//}
		return parent::Index();
	}
}

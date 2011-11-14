<?php
namespace System\Template;
use \CGAF;
use \System\Template\BaseTemplate;
use \Strings;
function smarty_loader($class) {
	$class = strtolower($class);
	$f = null;
	if (Strings::BeginWith($class, 'smarty_')) {
		$f = CGAF_VENDOR_PATH . '/Smarty/distribution/libs/plugins/' . $class . '.php';
	}
	if (Strings::BeginWith($class, 'smarty_internal_')) {
		$f = CGAF_VENDOR_PATH . '/Smarty/distribution/libs/sysplugins/' . $class . '.php';
	}
	if ($f && is_file($f)) {
		return CGAF::Using($f);
	} elseif ($f) {
		ppd($f);
	}
}
CGAF::RegisterAutoLoad('\\System\\Template\\smarty_loader');
include CGAF_VENDOR_PATH . "/Smarty/distribution/libs/Smarty.class.php";
class SmartyTemplate extends BaseTemplate implements \ITemplate {
	private $_callback;
	private $_templatePath;
	private $_controller;
	private $_buffer = null;
	/**
	 *
	 * Enter description here ...
	 * @var Smarty
	 */
	private $_smarty = null;
	function __construct($appOwner, $templatePath = null) {
		parent::__construct($appOwner, $templatePath);
		$this->_smarty = new \Smarty();
	}
	/**
	 * (non-PHPdoc)
	 * @see ITemplate::Assign()
	 */
	function Assign($varName, $value = null, $overwrite = true) {
		parent::assign($varName, $value, $overwrite);
	}
	/**
	 * (non-PHPdoc)
	 * @see ITemplate::getTemplatePath()
	 */
	function getTemplatePath() {
		return $this->_templatePath;
	}
	private function initSmarty() {
		$p = $this->getAppOwner()->getInternalCache()->getCachePath('.template', false) . '';
		$this->_smarty->setCompileDir($p);
		$this->_smarty->clearAllAssign();
		$this->_smarty->assign($this->_vars);
		$this->_smarty->debugging = CGAF_DEBUG;
	}
	/* (non-PHPdoc)
	 * @see ITemplate::Render()
	 */
	public function Render($templateName) {
		$ext = \Utils::getFileExt($templateName, false);
		switch ($ext) {
		case 'html':
		case 'tpl':
			$this->initSmarty();
			return $this->_smarty->fetch($templateName);
			break;
		default:
			return parent::render($templateName, true, false);
			break;
		}
	}
	function renderFile($fname, $return = true, $log = false) {
		$r = $this->render($fname);
		if (!$return) {
			\Response::write($r);
		}
		return $r;
	}
	/* (non-PHPdoc)
	 * @see ITemplate::getBuffer()
	 */
	public function getBuffer() {
		return $this->_buffer;
	}
	/* (non-PHPdoc)
	 * @see ITemplate::setContentCallback()
	 */
	public function setContentCallback($callback) {
		$this->_callback = $callback;
	}
	/* (non-PHPdoc)
	 * @see ITemplate::setTemplatePath()
	 */
	public function setTemplatePath($value) {
		$this->_templatePath = $value;
	}
	/* (non-PHPdoc)
	 * @see ITemplate::setAppOwner()
	 */
	public function setAppOwner($value) {
		$this->_appOwner = $value;
	}
}
?>
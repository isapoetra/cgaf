<?php
namespace System\Template;
use \CGAF;
use \System\Template\BaseTemplate;
use \String;
function smarty_loader($class) {
	$class = strtolower($class);
	if (String::BeginWith($class, 'smarty_internal_')) {
		return CGAF::Using(CGAF_VENDOR_PATH . '/Smarty/distribution/libs/sysplugins/' . $class . '.php');
	}
	if (String::BeginWith($class, 'smarty_')) {
		return CGAF::Using(CGAF_VENDOR_PATH . '/Smarty/distribution/libs/plugins/' . $class . '.php');
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
	/* (non-PHPdoc)
	 * @see ITemplate::Render()
	 */
	public function Render($templateName) {
		$ext = \Utils::getFileExt($templateName, false);
		switch ($ext) {
		case 'html':
		case 'tpl':
			$smarty = $this->_smarty;
			$p=$this->getAppOwner()->getInternalCache()->getCachePath('.template',false).'';
			$smarty->setCompileDir($p);
			$smarty->clearAllAssign();
			$smarty->assign($this->_vars);
			$smarty->debugging = true;
			return $smarty->fetch($templateName);
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
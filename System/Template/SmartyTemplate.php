<?php
function smarty_loader($class) {
	$class = strtolower ( $class );
	if (String::BeginWith ( $class, 'smarty_internal_' )) {
		return CGAF::Using ( CGAF_VENDOR_PATH . '/Smarty/distribution/libs/sysplugins/' . $class . '.php' );
	}
	if (String::BeginWith ( $class, 'smarty_' )) {
		return CGAF::Using ( CGAF_VENDOR_PATH . '/Smarty/distribution/libs/plugins/' . $class . '.php' );
	}
}
using("System.Web.Template.BaseTemplate");
CGAF::RegisterAutoLoad ( 'smarty_loader' );
include CGAF_VENDOR_PATH . "/Smarty/distribution/libs/Smarty.class.php";

class SmartyTemplate extends BaseTemplate implements ITemplate {
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
		parent::__construct ( $appOwner, $templatePath );
		$this->_smarty = new Smarty ();
	}
	/**
	 * (non-PHPdoc)
	 * @see ITemplate::Assign()
	 */
	function Assign($varName, $value = null, $overwrite = true) {
		parent::assign ( $varName, $value, $overwrite );
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
		$ext = Utils::getFileExt ( $templateName, false );
		switch ($ext) {
			case 'tpl' :
				$this->_smarty->reset();
				$this->_smarty->render($templateName);
				ppd ( $ext );
				break;
			default :
				return parent::render ( $templateName, true, false );
				break;
		}

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
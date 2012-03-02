<?php
defined("CGAF") or die("Restricted Access");
require "dwoo/Dwoo.php";

//------------------------ CGAF Support -------------
class DwooCGAF extends dwoo implements ITemplate {
	private $_appOwner = null;
	private $dwoo_data = array();

	/**
	 * Enter description here...
	 *
	 * @param IApplication $appOwner
	 */
	function __construct($appOwner) {
		parent::__construct();
		$this->_appOwner = $appOwner;
		$this->initialize();

		// Assign some defaults to dwoo
		$CI = $appOwner;
		$this->dwoo_data = new Dwoo_Data();
		$this->dwoo_data->js_files = array();
		$this->dwoo_data->css_files = array();
		$this->dwoo_data->CI = $CI;
		$this->dwoo_data->site_url = BASE_URL; // so we can get the full path to CI easily
		$this->dwoo_data->uniqid = uniqid();
		$this->dwoo_data->timestamp = time();

	}

	private function initialize() {
		$CI = $this->_appOwner;
		$config = $CI->getConfigs('template');
		foreach ( $config as $key => $val ) {
			$this->$key = $val;
		}
	}

	/**
	 * Enter description here...
	 *
	 * @param string $varName
	 * @param mixed $value
	 * @param boolean $overwrite
	 */
	function Assign($varName, $value = null, $overwrite = true) {
		if ($overwrite || ! isset($this->dwoo_data->$varName)) {
			$this->dwoo_data->$varName = $value;
		}
	}

	function Render($templateName) {
		$fname = Utils::ToDirectory($this->template_dir . $templateName . CGAF_CLASS_EXT);
    return $this->renderFile($fname);

	}

	function renderFile($fname, $return = false, $log = false) {
		if (! file_exists($fname)) {
			throw new Exception("Template File Not Found" . Logger::WriteDebug($fname));
		}
		Benchmark::mark('dwoo_parse_start');
		$tpl = new Dwoo_Template_File($fname);
		// render the template
		$template = $this->get($tpl, $this->dwoo_data);
		Benchmark::mark('dwoo_parse_end');
		Response::write($template);
	}
	/**
	 *
	 */
	public function isRendered() {

	}

	/**
	 *
	 */
	public function getBuffer() {

	}
/* (non-PHPdoc)
	 * @see ITemplate::addAsset()
	 */
	public function addAsset($asset) {
		// TODO Auto-generated method stub

	}

/* (non-PHPdoc)
	 * @see ITemplate::setContentCallback()
	 */
	public function setContentCallback($callback) {
		// TODO Auto-generated method stub

	}

/* (non-PHPdoc)
	 * @see ITemplate::getTemplatePath()
	 */
	public function getTemplatePath() {
		// TODO Auto-generated method stub

	}

/* (non-PHPdoc)
	 * @see ITemplate::setTemplatePath()
	 */
	public function setTemplatePath($value) {
		// TODO Auto-generated method stub

	}

/* (non-PHPdoc)
	 * @see ITemplate::setController()
	 */
	public function setController($value) {
		// TODO Auto-generated method stub

	}

/* (non-PHPdoc)
	 * @see ITemplate::setAppOwner()
	 */
	public function setAppOwner($value) {
		// TODO Auto-generated method stub

	}


}

function dwooAutoload($class) {
	if (String::BeginWith($class, 'Dwoo_')) {
		include DWOO_DIRECTORY . strtr($class, '_', DIRECTORY_SEPARATOR) . '.php';
		return true;
	}
	return false;
}
CGAF::RegisterAutoLoad("dwooAutoLoad");
//-----------------------EOF ---------
?>

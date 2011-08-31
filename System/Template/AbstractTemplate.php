<?php
namespace System\Template;
use System\Web\Utils\HTMLUtils;
use \URLHelper;
use \Utils;
use \System\Configurations\IConfiguration,\System\Configurations\Configuration;
use \Response;
use \AppManager;
use \Logger;
/**
 * Base Template engine
 *
 */
abstract class AbstractTemplate extends \Object implements \ITemplate, \System\Configurations\IConfiguration {
	protected $_templateName = null;
	protected $_vars = array ();
	protected $_appOwner;
	protected $_cacheDir;
	protected $_compileDir;
	private $isRendered = false;
	private $_buffer;
	private $_commonTemplatePath;
	private $_lastRenderFile;
	private $_contentCallBack;
	private $_configs;
	private $_content;
	/**
	 * Enter description here...
	 *
	 * @param IApplication $appOwner
	 * @param unknown_type $templatePath
	 */
	function __construct($appOwner, $templatePath = null) {
		$this->_configs = new Configuration ();
		$this->_appOwner = $appOwner ? : AppManager::getInstance();
		$this->_commonTemplatePath = Utils::ToDirectory ( CGAF_PATH . "System/template/common" );
		if ($templatePath !== null) {
			$this->_templatePath = $templatePath;
		}
		$this->Init ();
	}
	function clearParams() {
		$this->_vars = array();
	}
	public function setConfigs($configs) {
		$this->_configs = $configs;
	}
	public function setConfig($configName, $value = null) {
		return $this->_configs->setConfig ( $configName, $value );
	}
	public function Save($fileName = null) {
		//Nothing todo
	}
	public function getConfig($configName, $default = null) {
		return $this->_configs->getConfig ( $configName, $default );
	}

	public function Merge($_configs) {
		return $this->_configs->Merge ( $_configs );

	}
	/**
	 * return app Owner
	 *
	 * @return IApplication
	 */
	function getAppOwner() {
		return $this->_appOwner;
	}
	function setAppOwner($value) {
		$this->_appOwner = $value;
	}
	function getLiveData($data) {
		return $this->_appOwner->getLiveData ( $data );
	}
	function __set($name, $value) {
		return $this->assign ( $name, $value );
	}
	function getMainTemplate() {
		return $this->getAppOwner ()->getTemplate ();
	}
	function __get($name) {
		return isset ( $this->_vars [$name] ) ? $this->_vars [$name] : null;
	}

	protected function Init() {
	}
	public function getTemplatePath() {
		return $this->_templatePath;
	}
	public function setTemplatePath($value) {
		$this->_templatePath = $value;
	}
	function Assign($varName, $value = null, $overwrite = true) {
		if ($varName == null) {
			return $this;
		}
		if ($value == null) {
			if (is_array ( $varName )) {
				foreach ( $varName as $k => $v ) {
					$this->Assign ( $k, $v, $overwrite );
				}
				return $this;
			}
		}
		if (is_array ( $varName )) {
			throw new Exception ( "x" );
		}
		$overwrite = $overwrite || ! array_key_exists ( $varName, $this->_vars ) || (array_key_exists ( $varName, $this->_vars ) && $this->_vars [$varName] === null);
		if ($overwrite) {
			$this->_vars [$varName] = $value;
		}

		return $this;
	}

	public function setCompileDir($dir) {
		$this->_compileDir = $dir;
	}
	public function setCacheDir($dir) {
		$this->_cacheDir = $dir;
	}
	protected function searchTemplateFile($templateName) {
		$last = count ( $this->_lastRenderFile ) ? end ( $this->_lastRenderFile ) : null;
		$retval = array (
		$this->getTemplatePath () . DS . $templateName . $this->getTemplateFileExt (),
		dirname ( $last ) . DS . $templateName . $this->getTemplateFileExt () );
		if ($this->getAppOwner () instanceof IApplication) {
			$retval [] = $this->getAppOwner ()->getAppPath () . DS . 'Views' . DS . $templateName . $this->getTemplateFileExt ();
		}
		$retval = array_merge ( $retval, array (
		$last ? dirname ( $last ) . DS . basename ( $templateName . $this->getTemplateFileExt () ) : null,
		CGAF_SHARED_PATH . '/Views' . DS . $templateName . $this->getTemplateFileExt (),
		CGAF_SHARED_PATH . '/Views/shared/' . DS . $templateName . $this->getTemplateFileExt () )
		);
		return $retval;
	}

	function Render($templateName, $return = true, $log = false, $vars = null) {
		if (is_file ( $templateName )) {
			$fname = $templateName;
		} else {
			$search = $this->searchTemplateFile ( $templateName );
			$fname = null;
			foreach ( $search as $f ) {
				$f = Utils::ToDirectory ( $f );
				if ($f && is_file ( $f )) {
					$fname = $f;
					break;
				}
			}
		}
		if (! $fname) {
			$msg = 'file not found for template ' . $templateName;
			Logger::Warning ( $msg );
			if (CGAF_DEBUG) {
				return $msg;
			}
			return null;
		}
		foreach ( $this->_vars as $k => $v ) {
			$this->Assign ( $k, $v, true );
		}
		if ($vars != null) {
			foreach ( $vars as $k => $v ) {
				$this->Assign ( $k, $v, true );
			}
		}
		return $this->renderFile ( $fname, $return, $log );
	}

	public function renderFile($fname, $return = true, $log = false) {
		$oldreturn =$return;
		if (is_readable ( $fname )) {
			$fname = realpath ( $fname );

			if ($log) {
				Logger::write ( "Loading Template From $fname" );
			}
			foreach ( $this->_vars as $var => $val ) {
				$$var = $val;
			}
			$this->_lastRenderFile [] = $fname;
			$this->isRendered = false;
			Response::StartBuffer ();
			include $fname;
			$this->_buffer = Response::EndBuffer ();
			foreach ( $this->_vars as $var => $val ) {
				unset ( $$var );
			}
			array_pop ( $this->_lastRenderFile );
			if (! $oldreturn) {
				Response::write ( $this->_buffer );
			}
			$this->isRendered = true;
			return $this->_buffer;
		} else {
			throw new Exception ( "Template File not Found " . Logger::WriteDebug ( "@$fname" ) );
		}
	}
	protected function getCommonTemplatePath() {
		return $this->_commonTemplatePath;
	}
	public function getTemplateFileExt() {
		return CGAF_CLASS_EXT;
	}
	private function includeCommon($tpl) {
		$fname = $this->getCommonTemplatePath () . DS . $tpl . $this->getTemplateFileExt ();
		return $this->renderFile ( $fname, true );
	}
	/**
	 * (non-PHPdoc)
	 * @see System/Interface/ITemplate#isRendered()
	 */
	public function isRendered() {
		return $this->isRendered;
	}
	/**
	 * (non-PHPdoc)
	 * @see System/Interface/ITemplate#getBuffer()
	 */
	public function getBuffer() {
		return $this->_buffer;
	}
	function getVars() {
		return $this->_vars;
	}
	function getVar($varName, $def = null) {
		return isset ( $this->_vars [$varName] ) ? $this->_vars [$varName] : $def;
	}
	function setContent($value) {
		/*$ex = simplexml_load_string($value);
		 var_dump($ex);
		 ppd($this->_content);*/
		$this->_content = $value;
	}
	function getContent() {
		return $this->_content ? $this->_content : $this->getVar ( 'content' );
	}
}
?>

<?php
namespace System\Assets\Parsers;
use System\Configurations\Configuration;

use \Utils,\AppManager,\CGAF;
abstract class AbstractProjectParser {
	protected $_configs;
	protected $_files;
	protected $_sourcePath;
	protected $_destPath;
	protected $_projectPath;
	protected $_outputExt;
	private $_manifest=null;
	function __construct() {

	}

	function getCached($file, $dest, $join) {
		$dest = Utils::changeFileExt ( $dest, $this->_outputExt );
		$dest2 = Utils::changeFileName ( $dest, Utils::getFileName ( $dest ) . Utils::getAgentSuffix () );
		if (is_file ( $dest ) && is_file ( $dest2 )) {
			ppd ( array (
			$dest,
			$dest2 ) );
			return array (
			$dest,
			$dest2 );
		}
	}

	private function reset() {
		$this->_files = array ();
		$this->_sourcePath = $this->getConfig ( 'SourcePath', '' );
		if (AppManager::isAppStarted ()) {
			$cp = AppManager::getInstance ()->getCacheManager ()->getCachePath ();
		} else {
			$cp = CGAF::getCacheManager ()->getCachePath ();
		}
		$replacer = array (
				'$tmppath' => CGAF::getTempPath (),
				'$cachepath' => $cp );

		$this->_destPath = Utils::ToDirectory ( \Strings::replace ( $this->getConfig ( 'TargetPath', '$cachepath' ), $replacer ) );

	}

	function loadProjectFile($projectFile) {
		$this->_projectPath = dirname ( $projectFile );
		$config = new Configuration(null, false );
		$config->loadFile ( $projectFile );
		$this->setConfigs ( $config->getConfig ( 'assets.configs' ) );
		$files = $config->getConfig ( 'assets.files' );
		if ($files && is_array ( $files ['file'] )) {
			$this->addFile ( $files ['file'] );
		} else {
			$this->addFile ( $files );
		}
	}

	function setConfigs($configs) {
		if (is_array ( $configs )) {
			$configs = new Configuration ( $configs, false );
		} elseif ($configs === null) {
			$configs = new Configuration ( null, false );
		}
		$this->_configs = $configs;
		$this->reset ();
	}

	function addFile($file) {
		if (is_array ( $file )) {
			foreach ( $file as $f ) {
				$this->addFile ( $f );
			}
			return;
		}

		if (in_array ( $file, $this->_files )) {
			return;
		}
		if ($this->canHandle ( $file )) {
			if (is_file ( $file )) {
				$this->_files [] = $file;
			} else {
				$fname = Utils::ToDirectory ( $this->_projectPath . DS . $this->_sourcePath . DS . $file );
				if (is_file ( $fname )) {
					$this->_files [] = $fname;
				}
			}
		}
	}

	function getConfig($configName, $def = null) {
		if (! $this->_configs) {
			$this->_configs = new Configuration ( null, false );
		}
		return $this->_configs->getConfig ( $configName, $def );
	}

	abstract protected function _parseFile($files, $dest, $join = false);

	abstract protected function canHandle($file);

	abstract protected function _buildString($s);

	function build() {
		$dest = Utils::ToDirectory ( $this->_destPath . DS . $this->getConfig ( 'Target' ) );
		Utils::makeDir ( dirname ( $dest ) );
		if (CGAF_DEBUG) {
			Utils::removeFile ( $dest );
		}
		$f = $this->getConfig ( 'assets.files' );
		return $this->_parseFile ( $this->_files, $dest, $f ['join'] );
	}

	protected function parseFiles($files, $dest) {
		Utils::makeDir ( dirname ( $dest ) );

		return $this->_parseFile ( $files, $dest, true );
	}

	function parseFile($file, $dest, $join = false) {

		return $this->_parseFile ( $file, $dest, $join );
	}
}
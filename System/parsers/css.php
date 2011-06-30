<?php

class CSSProjectParser extends AssetFileParser {
	function __construct() {
		parent::__construct ();
		$this->_outputExt = 'css';
	}
	protected function canHandle($file) {
		$ext = Utils::getFileExt ( $file, false );
		return in_array ( $ext, array (
			'css' ) );
	}
	
	protected function _parseFile($files, $dest, $join = false) {
		if (is_array ( $files )) {
			$retval = array ();
			foreach ( $files as $file ) {
				$r = $this->_parseFile ( $file, $dest, $join );
				if ($r) {
					$retval [] = $r;
				}
			}
			return $retval;
		}
		
		if (is_array ( $dest )) {
			ppd ( $dest );
		}
		$files = realpath ( $files );
		if (! $files) {
			return null;
		}
		$dest = Utils::changeFileExt ( $dest, 'css' );
		$dest2 = Utils::changeFileName ( $dest, Utils::getFileName ( $dest ) . Utils::getAgentSuffix () );
		$files = realpath ( $files );
		if (! $join) {
			Utils::removeFile ( $dest );
			Utils::removeFile ( $dest2 );
		}
		$compress = array (
			$files => $dest, 
			Utils::changeFileName ( $files, Utils::getFileName ( $files ) . Utils::getAgentSuffix () ) => $dest2 );
		$retval = array ();
		foreach ( $compress as $k => $v ) {
			if (is_file ( $k )) {
				if (! is_file ( $v )) {
					file_put_contents ( $v, '' );
				}
				$str = WebUtils::parseCSS ( $k, true, ! CGAF_DEBUG );
				file_put_contents ( $v, $str, FILE_APPEND );
				$retval[]=$v;
			}
		}
		return $retval;
	}
	function parseFile($file, $dest, $join = false) {
		
		$o = WebUtils::$_lastCSS;
		$r = parent::parseFile ( $file, $dest, $join );
		WebUtils::$_lastCSS = $o;
		return $r;
	}
	/**
	 * @param unknown_type $s
	 */
	protected function _buildString($s) {
		
		return WebUtils::parseCSS ( $s, false, ! CGAF_DEBUG );
	}

}
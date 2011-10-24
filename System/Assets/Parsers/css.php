<?php
namespace System\Assets\Parsers;
use System\Web\WebUtils,\Utils;
class CSSProjectParser extends AbstractProjectParser {
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
					if (is_array($r)) {
						$retval = array_merge($retval,$r);
					}else{
						$retval [] = $r;
					}
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
		$dest2 = dirname($dest).DS.substr(basename($dest),0,strpos(basename($dest),'.')).	Utils::getAgentSuffix ().'.min.css';
		//Utils::changeFileName ( $dest, Utils::getFileName ( $dest ) . Utils::getAgentSuffix () );
		
		$files = realpath ( $files );
		if (! $join) {
			Utils::removeFile ( $dest );
			Utils::removeFile ( $dest2 );
		}
		$fagent = dirname($files).DS.substr(basename($files),0,strpos(basename($files),'.')).	Utils::getAgentSuffix ().'.css';
		$compress = array (
		$files => $dest,
		$fagent => $dest2 );
		$retval = array ();
		foreach ( $compress as $k => $v ) {
			if (is_file ( $k )) {
				if (! is_file ( $v )) {
					file_put_contents ( $v, '' );
				}
				$str = \Compressor::compressFile( $k);
				file_put_contents ( $v, $str, FILE_APPEND );
				$retval[]=$v;
			}
		}
		return $retval;
	}
	function parseFile($file, $dest, $join = false) {
		$o = WebUtils::$_lastCSS;
		$r = $this->_parseFile($file, $dest, $join );
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
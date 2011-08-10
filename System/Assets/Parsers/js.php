<?php
namespace System\Assets\Parsers;
use \Utils;
class JSProjectParser extends AbstractProjectParser {

	/**
	 * @param string $dest
	 */
	protected function _parseFile($files, $dest, $join = false) {
		if (is_array ( $files )) {
			$retval = array ();
			if ($join) {
				Utils::removeFile($dest);
			}
			foreach ( $files as $file ) {
				$r = $this->_parseFile ( $file, $dest, $join );
				if ($r) {
					$retval  = array_merge($retval,$r);
				}
			}
			return $retval;
		}

		$retval = array ();
		$dest = Utils::changeFileExt ( $dest, 'min.js' );

		$content = null;
		$content = null;

		if (! is_file ( $files ))
			return null;

		$content = file_get_contents ( $files );
		if (! $join) {
			file_put_contents ( $dest, "\n;/*" . basename ( $files ) . "*/\n;" . $this->_buildString ( $content ) );
		} else {
			file_put_contents ( $dest, "\n;/*" . basename ( $files ) . "*/\n;" . $this->_buildString ( $content ), FILE_APPEND );
		}

		$retval [] = $dest;
		return $retval;
	}

	/**
	 * @param unknown_type $file
	 */
	protected function canHandle($file) {
		$ext = Utils::getFileExt ( $file, false );
		return in_array ( $ext, array (
			'js' ) );
	}

	/**
	 * @param $s
	 */
	protected function _buildString($s) {
		if (CGAF_DEBUG) {
			return $s;
		}
		return JSUtils::pack ( $s );
	}

}
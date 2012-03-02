<?php
using ( "Libs.Zend" );
using ( 'zend.Pdf' );
class PDF extends Zend_Pdf  {
	private $_fname;
	function __construct($fname,$edit=false) {
		$this->_fname = $fname;
		parent::__construct($fname,null,true);

	}
	/**
	 *
	 * @param $destPath
	 * @param $destFormat
	 * @param $configs
	 * @return path of converted documents
	 */
	function convertTo($destPath,$destFormat,$configs=null) {
		if (!is_file($this->_fname)) {
			throw new IOException('file not found %s',Logger::WriteDebug($this->_fname));
		}
		return PDFUtils::ConvertTo($this->_fname, $destPath,$destFormat,$configs);
	}
	function getMeta() {
		$retval = $this->properties;
		$retval = array_merge ( $retval, array ('totalpages' => count ( $this->pages ) ) );
		return $retval;
	}
	function toSWF($dest, $maxPercent = 100) {
		$meta = $this->getMeta ();
		$maxPage = $meta ['totalpages'] * $maxPercent / 100;
		$maxPage = $maxPage < 1 ? 1 : round ( $maxPage );
		return SWFHelper::pdf2swf ( $this->_fname, $dest, $maxPage );
	}
}

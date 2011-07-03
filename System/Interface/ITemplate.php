<?php
if (! defined ( "CGAF" ))
	die ( "Restricted Access" );
interface ITemplate {
	/**
	 *
	 * Enter description here ...
	 * @param mixed $varName
	 * @param mixed $value
	 * @param boolean $overwrite
	 */
	function Assign($varName, $value = null, $overwrite = true);

	/**
	 *
	 * @param $templateName
	 * @return boolean
	 */
	function Render($templateName);
	/**
	 *
	 * @param String $fname
	 * @param boolean $return
	 * @param boolean $log
	 */
	public function renderFile($fname, $return = true, $log = false);
	/**
	 *
	 * Enter description here ...
	 * @param string $dir
	 */
	public function setCompileDir($dir);

	/**
	 *
	 * Enter description here ...
	 * @param string $dir
	 */
	public function setCacheDir($dir);
	/**
	 *
	 * @return boolean
	 */
	public function isRendered();
	/**
	 * get buffer of last rendered template
	 * @return string
	 */
	public function getBuffer();
	


	/**
	 *
	 * Enter description here ...
	 * @return String
	 */
	function getTemplatePath();
	/**
	 *
	 * Enter description here ...
	 * @param String $value
	 */
	function setTemplatePath($value);
	/**
	 *
	 * Enter description here ...
	 * @param IMVCController $value
	 */
	function setController($value);

	/**
	 *
	 * Enter description here ...
	 * @param IApplication $value
	 */
	function setAppOwner($value);
}
?>
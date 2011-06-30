<?php
if (!defined("CGAF") ) die("Restricted Access");

interface IObject {

	/**
	 * getAllowed properties
	 * @return array
	 */
	function setValue ($value);

	function getValue ();

	function assign ($var, $val=null); 
}
?>
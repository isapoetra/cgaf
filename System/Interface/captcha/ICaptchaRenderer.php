<?php
if (!defined("CGAF")) die("Restricted Access");
interface ICaptchaRenderer {
	function setCode($code);
	function renderImage();
	function render();
}

?>
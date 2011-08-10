<?php
namespace System\Captcha;
interface ICaptchaRenderer {
	function setCode($code);
	function renderImage();
	function render();
}

?>
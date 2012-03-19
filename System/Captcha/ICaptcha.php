<?php

/**
 * ICaptcha.php
 * User: e1
 * Date: 3/16/12
 * Time: 12:06 AM
 */
namespace System\Captcha;
interface ICaptcha extends \IRenderable{
	function validateRequest();
}

<?php

interface IController {
	function render($route = null, $vars = null, $contentOnly = null);
}

?>
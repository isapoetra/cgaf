<?php
namespace System\Web;
if (!defined("CGAF")) die("Restricted Access");

class JSScript extends TWebHTML {

	function __construct () {
		parent::__construct("script", false);
	}
}
?>
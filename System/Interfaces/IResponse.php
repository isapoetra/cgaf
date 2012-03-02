<?php
interface IResponse {
	function Redirect($url = null);
  function write($s, $attr = null);
  function getBuffer();
  function clearBuffer();
}
?>

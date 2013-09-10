<?php
interface IResponse
{
    function Redirect($url = null);

    function write($s, $attr = null);

    function getBuffer();

    function clearBuffer();

    function StartBuffer();

    function EndBuffer($flush = true);

    function flush();

    function forceContentExpires();

}

?>

<?php
namespace System\Applications;
/**
 *
 */
interface IWebApplication extends IApplication {
    function addClientScript($script);
    function addClientDirectScript($script);
}	

?>
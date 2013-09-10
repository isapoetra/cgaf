<?php
namespace System\Applications;
/**
 *
 */
interface IWebApplication extends IApplication
{
    /**
     * @param $script
     * @return mixed
     */
    function addClientScript($script);

    /**
     * @param array||string $script
     * @return mixed
     */
    function addClientDirectScript($script);
}

?>
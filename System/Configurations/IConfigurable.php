<?php
namespace System\Configurations;
/**
 * User: Iwan Sapoetra
 * Date: 04/03/12
 * Time: 11:41
 */
interface IConfigurable {
  function getConfig($configName, $default = null);
  function setConfig($configName, $value = null);

}

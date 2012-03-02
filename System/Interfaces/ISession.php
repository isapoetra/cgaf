<?php
if (!defined("CGAF"))
  die("Restricted Access");
use System\Configurations\IConfigurable;
interface ISession extends IObject,IConfigurable {

  function Start();

  function isStarted();

  function &get($name, $default = null);

  function set($name, $value);

  function remove($varname);

  //Session Handler


  function open($savePath, $sessName);

  function read($sessID);

  function write($sessID, $sessData);

  function destroy($sessID = null);

  function gc($sessMaxLifeTime);

  public function reStart();

  public function getId();

  public function registerState($stateGroup);

  public function unregisterState($stateGroup);

  public function setState($stateGroup, $stateName, $value);

  function &setStates(\System\Session\sessionStateHandler $state);

  public function getStates();

  public function getState($stateGroup, $stateName, $default = null);
}

?>
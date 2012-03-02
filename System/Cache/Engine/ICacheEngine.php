<?php
namespace System\Cache\Engine;
interface ICacheEngine {
  function remove($id, $group, $force = true, $ext = null);

  function get($id, $prefix, $suffix = null, $timeout = NULL);

  function clear();

  public function putString($s, $id, $ext = null);

  public function getId($o);

  public function isCacheValid($fname, $timeout = null);

  public function putFile($fname, $id, $callback = null, $group = "misc");

  function getContent($id, $prefix, $suffix = null, $timeout = NULL);

  public function setCachePath($path);

  public function put($id, $o, $group, $add = false, $ext = null);
}

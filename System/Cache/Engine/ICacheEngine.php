<?php
namespace System\Cache\Engine;
interface ICacheEngine {
	function remove($id, $group, $force = true, $ext = null);
	function get($id, $prefix, $suffix = null, $timeout = NULL);
}

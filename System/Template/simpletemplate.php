<?php
namespace System\Template;
class SimpleTemplate extends BaseTemplate {
	public function renderFile($fname, $return = true, $log = false) {
		$content = file_get_contents($fname);
		foreach($this->_vars as $k=>$v) {
			if (is_string($v) || is_numeric($v)) {
				$content = str_replace('{$'.$k.'}',$v,$content);
			}
		}
		return $content;
	}
}

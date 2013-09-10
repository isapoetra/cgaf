<?php
namespace System\Template;

class SimpleTemplate extends BaseTemplate
{
    public function renderFile($fname, $return = true, $log = false)
    {
        $content = file_get_contents($fname);
        foreach ($this->_vars as $k => $v) {
            if (is_string($v) || is_numeric($v)) {
                $tmp = str_replace('{$' . $k . '}', $v, $content, $count);
                if ($count) {
                    $content = $tmp;
                }
            }
        }
        return $content;
    }
}

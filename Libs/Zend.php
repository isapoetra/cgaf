<?php
define('ZEND_LIB_PATH',Utils::ToDirectory(CGAF_VENDOR_PATH.DS.'Zend'.DS.'library/'));
CGAF::addClassPath('Zend',ZEND_LIB_PATH);
?>
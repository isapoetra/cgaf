<?php
namespace System\Mail\Transport;
use System\Mail\MailObject;

abstract class AbstractTransport {
	private $_base ='common';
	function init($base=null) {
		$this->_base=$base;
	}
	abstract function send(MailObject $o);
	function getConfigs($configName=null) {
		return \MailHelper::getConfigs($this->_base.($configName ? '.'.$configName : ''));
	}
	function getConfig($configName,$default=null) {
		return \MailHelper::getConfig($this->_base.'.'.$configName,$default);
	}
}
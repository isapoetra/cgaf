<?php

abstract class TCaptchaBase extends TImage implements
		ICaptchaRenderer {
	protected $_container;
	protected $_image;
	private $_code;
	function __destruct(){
		if ($this->_image) {
			@imagedestroy($this->_image);
		}
	}
	function __construct(ICaptchaContainer $container) {
		$this->_container = $container;
		$this->_initialize();
	}
	protected function _initialize() {

	}
	protected function getOptions() {
		return $this->_container->getConfigs();
	}
	/**
	 * @param unknown_type $code
	 */
	public function setCode($code) {
		$this->_code = $code;
	}

	protected function getCode() {
		return $this->_code;
	}
	function __get($name) {
		$retval = $this->_container->getConfig($name);
		return $retval ==null ? parent::__get($name) : $retval;
	}
	function getWidth() {
		return $this->getConfig('width');
	}

	function getHeight() {
		return $this->getConfig('height');
	}

	function getConfig($name, $def = null) {
		return $this->_container->getConfig($name, $def);
	}
}
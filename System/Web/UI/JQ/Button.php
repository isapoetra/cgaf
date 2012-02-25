<?php
namespace System\Web\UI\JQ;
use System\JSON\JSON;

class Button extends Control {
	protected $_methods = array ();

	function __construct($id, $attr = null) {
		parent::__construct($id);
		$this->setConfigs(array (
				'disabled' => false,
				'text' => true,
				'icons' => Array (
						'primary' => null,
						'secondary' => null
				)
		));
		$this->setTag('button');
		$this->setAttr($attr);
	}

	function addMethod($m, $v, $funcvalue = false) {
		switch (strtolower($m)) {
			case 'addclass' :
				$this->setAttr('class', $v);
				break;
			default :
				;
				break;
		}
		$val = array (
				'method' => $m,
				'value' => $v,
				'f' => $funcvalue
		);
		$this->_methods [] = $val;
	}



	function RenderScript($return = false) {
		$retval = parent::RenderScript(true);
		if ($this->_tag === 'button') {
			$script = '$(\'#' . $this->getId() . '\').button(' . JSON::encodeConfig($this->_configs,array_keys($this->_events)) . ')';

			foreach ( $this->_methods as $m ) {
				$script .= '.' . $m ['method'] . '(' . ($m ['f'] ? $m ['value'] : '\'' . $m ['value'] . '\'') . ')';
			}
			$script .= ';';

			$this->getAppOwner()->addClientScript($script);
		}
		return $retval;
	}
}
?>
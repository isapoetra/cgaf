<?php
namespace System\Web\UI\Ext;
class CustomComponent extends Component {

	function __construct($class, $config = null, $ignore = null, $renderto = true) {
		parent::__construct ( $config, $class, "g" );
		$this->addIgnoreConfigStr ( $ignore );
		if ($renderto) {
			$this->setConfig ( "renderTo", Request::get ( "renderTo" ) );
		}
	}
}
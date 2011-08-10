<?php
namespace System\Web\UI\Controls;
class Expandable extends WebControl {
	private $_content;
	function __construct($id) {
		parent::__construct('div');
		$this->setId($id);
	}
	function setContent($content) {
		$this->_content = $content;
	}
	function prepareRender() {
		$app = $this->getAppOwner();
		$id = $this->getId();
		$sc = <<< EOT
$('#$id').expandable();
EOT;
		$app->addClientScript($sc);
		if ($this->_content) {
			$c = new WebControl("div");
			$c->setId($this->getId() . '-expander-body');
			$c->setText($this->_content);
			$this->addChild($c);
		}
		parent::prepareRender();
	}
}

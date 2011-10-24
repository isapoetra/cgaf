<?php
class StartMenu extends MenuItem {
	private $_template;
	function __construct($id) {
		parent::__construct($id);
		$this->_template = AppManager::getInstance()->getTemplate();
	}
	function Render($return = false) {
			$items =  parent::renderChilds(false);
			$id = $this->getId();

			$retval = '<div class="start-menu">'
			.HTMLUtils::renderButton('button','Start','Start',array('class'=>'start-menu',"id"=>"$id-button"))
			.'</div><ul style="display:none" id="'.$id.'-menu">'
			.$items
			.'</ul>';

			$retval .= <<< EOT
<script language="javascript">
	jQuery('#$id-button').startMenu({
		menutoshow:'$id-menu'
	});
</script>
EOT;
			return $retval;
	}
}
?>

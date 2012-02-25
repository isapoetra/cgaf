<?php
namespace System\Web\SWF;
use System\Web\SWFObject;
class YahooPingBox extends SWFObject {
	////<object id="pingbox82mrydsxep000" type="application/x-shockwave-flash" data="http://wgweb.msg.yahoo.com/badge/Pingbox.swf" width="240" height="420"><param name="movie" value="http://wgweb.msg.yahoo.com/badge/Pingbox.swf" /><param name="allowScriptAccess" value="always" /><param name="flashvars" value="wid=40iYNniyUGP.rAijgdl.rQChRA--" /></object>
	function __construct($pingboxId) {
		parent::__construct('http://wgweb.msg.yahoo.com/badge/Pingbox.swf');
		$this->setId('pingbox82mrydsxep000');
		$this->setParam('flashvars', $pingboxId);
		$this->setAttr('width', 240);
		$this->setAttr('height',420); 
	}
}
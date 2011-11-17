<?php
namespace System\API;
class Paypal extends PublicApi {
	function button($id) {
		$r = <<< EOT
<form action="https://www.paypal.com/cgi-bin/webscr" method="post">
<input type="hidden" name="cmd" value="_s-xclick">
<input type="hidden" name="hosted_button_id" value="$id">
<input type="image" src="https://www.paypalobjects.com/en_US/i/btn/btn_donate_SM.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">
<img alt="" border="0" src="https://www.paypalobjects.com/en_US/i/scr/pixel.gif" width="1" height="1">
</form>
EOT;
		return $r;
	}
	function donnate() {
		return $this->button(\AppManager::getInstance()->getconfig('paypal.donnate.button', 'N86XAEG4M75V6'));
	}
}

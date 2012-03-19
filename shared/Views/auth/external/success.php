<?php
use System\Web\JS\JSUtils;
echo '<noscript>authentification success please close this window</noscript>';
$script = <<< EOT
if (window.opener) {
	window.opener.location.reload();
	window.close();
}
EOT;
echo JSUtils::renderJSTag($script, false);
?>
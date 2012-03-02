<?php
namespace System\API;
class Yahoo extends PublicApi {
	function onlineStatus($config) {
		if (is_string ( $config )) {
			$config = array (
					'username' => $config
			);
		}
		// $config = array_merge ( $this->_config ['onlineStatus'], $config );
		$cfg = new \stdClass ();
		\Convert::toObject ( $config, $cfg, true );
		if (! isset ( $cfg->username )) {
			return null;
		}
		// <span>' . __ ( 'share.ym', 'Send Message' ) . '</span>
		return '<a href="ymsgr:sendim?' . $cfg->username . '"><img border="0" src="http://opi.yahoo.com/yahooonline/u=' . $cfg->username . '/m=g/t=2/l=us/opi.jpg"/></a>';
	}
}

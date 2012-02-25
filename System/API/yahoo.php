<?php
namespace System\API;
class Yahoo extends PublicApi {
	function onlineStatus($config) {
		if (is_string($config)) {
			$config = array(
					'username' => $config);
		}
		if (!isset($config['username'])) {
			return null;
		}
		//'<a href="http://messenger.yahoo.com/edit/send/?.target=' . $row->descr . '"></a>' .
		return '<a href="ymsgr:sendim?' . $config['username'] . '"><img border="0" src="http://opi.yahoo.com/yahooonline/u=' . $config['username'] . '/m=g/t=2/l=us/opi.jpg"/><span>'.__('share.ym','Send Message').'</span></a>';
	}
}

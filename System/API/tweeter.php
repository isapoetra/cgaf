<?php
namespace System\API;
class Tweeter extends PublicApi {
	function __construct() {
		parent::__construct();
		$this->_apijs = array(
				'button' => \URLHelper::getCurrentProtocol() . '://platform.twitter.com/widgets.js');
	}
	function button($dataCount = 'vertical') {
		$this->init(__FUNCTION__);
		//<a href="https://twitter.com/share" class="twitter-share-button" data-count="vertical">Tweet</a><script type="text/javascript" src="//platform.twitter.com/widgets.js"></script>
		return '<a href="https://twitter.com/share" class="twitter-share-button" data-count="' . $dataCount . '">Tweet</a>';
	}
}

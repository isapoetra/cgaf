<?php
using('System.Web.feed.base');
abstract class FeedBuilder {
	private  function __construct() {
	}
	public static function getInstance($type='rss') {
		using('System.Web.feed.'.strtolower($type));
		$c =  'T'.$type.'Feed';
		$c = new $c;
		return $c;
	}
	public static function build($type,$data) {

		$instance = self::getInstance($type);
		if ($instance) {
			$instance->setData($data);
			$instance->render(false);
			CGAF::doExit();
		}else{
			throw new SystemException('Unknown Feed Type $type');
		}
	}
}
<?php
namespace System\Controllers;
use System\MVC\Controller;
class SearchController extends Controller {
	function isAllow($access = 'view') {
		switch ($access) {
		case 'view':
		case 'index':
		case 'opensearch':
			return true;
			break;
		default:
			;
			break;
		}
	}
	function opensearch() {
		$r = \request::get('r');
		$rows = array();
		if (!$q = \Request::get('q')) {
			\Request::isAJAXRequest(true);
			$mime = '';
			switch ($r) {
			case 'def':
				$mime = 'application/opensearchdescription+xml';
				$base = BASE_URL;
				$s = <<< EOT
<?xml version="1.0" encoding="UTF-8" ?>
<OpenSearchDescription xmlns="http://a9.com/-/spec/opensearch/1.1/"
                       xmlns:moz="http://www.mozilla.org/2006/browser/search/">
	<ShortName>CGAF</ShortName>
	<Description>Search CGAF, Multi Application Framework</Description>
	<InputEncoding>UTF-8</InputEncoding>
	<Image width="16" height="16" type="image/x-icon">{$base}assets/images/favicon.ico</Image>
	<Url type="text/html" method="get" template="{$base}search/opensearch/?q={searchTerms}&amp;pw={startPage}">
	</Url>
	<Url type="application/x-suggestions+json" template="{$base}search/opensearch/?__data=json&amp;r=suggest&amp;q={searchTerms}"/>
</OpenSearchDescription>
EOT;
				break;
			default:
				break;
			}
			return \Streamer::StreamString($s, null, $mime);
		} else {
			switch ($r) {
			case 'suggest':
			//trick to force result as array
				$retval = array(
						array(),
						'fir' => array(
								'firefox',
								'firecache',
								'firebird'));
				return array_values($retval);
				break;
			default:
				$res = $this->doSearch($q);
				break;
			}
		}
		return parent::render('result', array(
				'q' => $q,
				'rows' => $rows));
	}
	private function doSearch($q, $config = array()) {
		$owner = $this->getAppOwner();
		$providers = $owner->getConfigs('searchproviders', array(
						'news'));
		$results = array();
		foreach ($providers as $p) {
			if (is_string($p)) {
				$p = $this->getController($p);
			}
			if ($p instanceof \ISearchProvider) {
				$results[] = $p->search($q, $config);
			}
		}
		return $results;
	}
}

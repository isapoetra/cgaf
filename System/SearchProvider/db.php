<?php
namespace System\SearchProvider;
use System\DB\DBQuery;

abstract class TSearchProviderDB extends DBQuery implements \ISearchProvider {

	function __construct(\ISearchEngine $se) {
		parent::__construct ( $se->getAppOwner());
	}
	private function isValidConfig($config) {
		$keys = array(
			'table',
			'searchFieldId',
			'searchLink',
			'title',
			'searchField'
		);
		$ok = true;
		foreach($keys as $v) {
			if (!isset($config[$v])) {
				$ok=false;
				break;
			}
		}
		return $ok;
	}
	function search($s,$config) {
		if (!$this->isValidConfig($config)) {
			throw new SystemException('Invalid Configuration');
		}
		$this->clear()
		->addTable ( $config ["table"] );

		$search = explode(',',$config ["searchField"]);

		foreach ($search as $field) {
			$this->where ( "lower(" . $this->quoteField($field) . ") like '%" . $this->quote ( $s, false ) . "%'" ,'or');
		}
		$r =  new TSearchItemCollection();
		$r->resultCount=$this->select('count(*) as _count')->loadObject()->_count;

		$rows=$this->clear('field')
		->select ( $config ["resultField"] )
		->loadObjects(null,Request::get('_p',0),Request::get('_pw',10));


		foreach ($rows as $k=>$v) {
			$v->link = $config['searchLink'].$v->{$config ["searchFieldId"]};
			$p = new TSearchItem($v);
			$r->add($p);
		}
		return $r;
	}

}
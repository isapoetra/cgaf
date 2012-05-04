<?php
namespace System\Controllers;
use System\ACL\ACLHelper;

use System\Template\TemplateHelper;

use System\Search\SearchResults;
use System\MVC\Controller;
class News extends Controller {
	function isAllow($access='view') {
		switch ($access) {
			case 'view':
			case 'lists':
			case 'index':
			case 'images':
			case 'detail':
				return true;
		}
		return parent::isAllow($access);
	}
	function search($s, $config) {
		$retval = new SearchResults();
	}
	function images() {
		$u = \URLHelper::explode($_REQUEST['__url']);
		$path = $u['path'];
		array_shift($path);
		$id = $path[1];
		$fname= basename($path[2]);
		$o = $this->getModel()->load($id);

		if ($o) {
			$path = $this->getInternalPath($o->id.'/images/');
			if (is_file($path.$fname)) {
				return \Streamer::Stream($path.$fname);
			}
		}
	}
	function detail($args = null, $return = null) {
		$id =\Request::get('id');
		if ($id) {
			$m = $this->getModel()->reset('detail');
			$row = $m->where('id='.$m->quote($id))->loadObject();
			if ($row) {
				switch ((int)$row->type) {
					case 0:
						$f = $this->getInternalPath($row->id).'index.html';
						if (is_file($f)) {
							$row->contents=  TemplateHelper::renderFile($f,array(
								'imageurl'=>\URLHelper::add(BASE_URL,'news/images/id/'.$row->id.'/')
							),$this);
						}else{
							$row->contents = CGAF_DEBUG ? $f : '';
						}
				}
			}
			return parent::renderView(__FUNCTION__,array('row'=>$row));
		}
	}
}

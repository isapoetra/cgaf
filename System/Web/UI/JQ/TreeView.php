<?php

class JQTreeView extends JQControl {
	private $_url;
	private $_asyncMode = true;
	private $_model;
	private $_defaultMapping = array (
			'text' => 'text', 
			'id' => 'id', 
			'parent' => 'parent' );
	private $_textParser =null;
	private $_columnMapping = null;

	function __construct($id, $url, $template = null) {
		parent::__construct ( $id, $template );
		$url = URLHelper::addParam ( $url, '__s=json&_treeid=' . $id );
		$this->_url = $url;
	}

	public function setModel($model, $columnMapping = null) {
		$this->_columnMapping = $columnMapping ? $columnMapping : $this->_defaultMapping;
		$this->_model = $model;
	
	}

	private function colMapping($id) {
		return isset ( $this->_columnMapping [$id] ) ? $this->_columnMapping [$id] : $this->_defaultMapping [$id];
	}
	public function setTextParser($value) {
		$this->_textParser =$value;
	}
	private function parseText($s,$o) {
		if ($this->_textParser) {
			return call_user_func($this->_textParser,$s,$o,$this);
		}
		return String::replace($s , $o,$s,false,0,'#','#');
	}
	private function parseModelRows($rows,$loadChild =false) {
		$retval = array ();
		foreach ( $rows as $row ) {
			$child = $this->loadAll ( $row->id ,$loadChild);
			$r = new stdClass ();
			$r->id = $row->id;
			$r->text = $this->parseText($row->text,$row);
			$r->expanded = true;
			//$r->hasChildren  =count ( $child )>0;
			//$r->collapsed =false;
			
			//if ($this->_asyncMode) {
			//$r->hasChildren = count ( $child ) > 0;
			//} else {
			//if (count ( $child ) > 0) {
				$r->children = $child;
			//}
			//}
			$retval [] = $r;
		}
		return $retval; 
	}

	private function loadAll($parent = 0,$loadChild =false) {
		$m = $this->_model;
		$m->clear ( );
		$m->clear( 'field');
		$m->select ( $this->colMapping ( 'id' ), 'id' );
		$m->select ( $this->colMapping ( 'parent' ), 'parent' );
		$m->select ( $this->colMapping ( 'text' ), 'text' );
		$m->where ( $this->colMapping ( 'parent' ) . '=' . $m->quote ( $parent ) );
	
		return $this->parseModelRows ( $m->loadAll () ,$loadChild);
	
	}

	private function renderData() {
		if (! $this->_model) {
			throw new SystemException ( 'Invalid Model' );
		}
		return $this->loadAll ( (int)Request::get ( 'root', 0 ) );
	
	}

	/**
	 * @param boolean $return
	 */
	public function Render($return = false) {
		if (Request::isDataRequest ()) {
			return $this->renderData ();
		}
		$e = $this->getTemplate ()->getAppOwner ()->getJSEngine ();
		$this->getTemplate ()->addAsset ( $e->getAsset ( 'plugins/jquery-treeview/jquery.treeview.js' ) );
		if ($this->_asyncMode) {
			$this->getTemplate ()->addAsset ( $e->getAsset ( 'plugins/jquery-treeview/jquery.treeview.async.js' ) );
		}
		$retval = null;
		$this->getTemplate ()->addAsset ( $e->getAsset ( 'plugins/jquery-treeview/jquery.treeview.css' ) );
		$this->setConfig ( 'url', $this->_url );
		
		$id = $this->getId ();
		$parent = $this->getConfig ( 'renderTo', 'body' );
		$this->removeConfig('renderTo');
		//pp($this->_configs);
		$configs = JSON::encodeConfig ( $this->_configs );
		//FIXME DONOT remove until prepareoutput parser on web application fixed 
		$c = urlencode ( "<div id=\"$id\"></div>" );
		$js = <<<EOT
		if ($('#$id').length ===0) {
			$(decodeURIComponent('$c').replace('+',' ')).appendTo('$parent');
		}
EOT;

		$js .= ';$(\'#' . $this->getId () . '\').treeview(' . $configs . ')';
		$this->getTemplate ()->addClientScript ( $js );
		if (! $return) {
			Response::write ( $retval );
		}
		return $retval;
	}

}
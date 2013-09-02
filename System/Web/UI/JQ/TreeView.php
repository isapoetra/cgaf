<?php

namespace System\Web\UI\JQ;

use System\MVC\Models\TreeModel;
use System\MVC\MVCHelper;
use System\Web\JS\CGAFJS;
use \URLHelper;
use \Request;
use \Response;
use System\JSON\JSON;
use System\Exceptions\SystemException;
use \Strings;
use System\API\PublicApi;

class TreeView extends Control {
	protected $_url;
	protected $_asyncMode = true;
	/**
	 *
	 *
	 *
	 *
	 * Enter description here ...
	 *
	 * @var \System\MVC\Model
	 */
	private $_model;
	private $_defaultMapping = array (
			'text' => 'text',
			'id' => 'id',
			'parent' => 'parent' 
	);
	private $_textParser = null;
	private $_columnMapping = null;
	protected $_nodeText;
	private $_rootNode;
	private $_baseLocale = null;
	private $_defaultRoot = 0;
	function __construct($id, $dataURL = null, $baseLocale = null, $root = 0) {
		parent::__construct ( $id );
		$dataURL = $dataURL ? $dataURL : \URLHelper::add ( APP_URL, MVCHelper::getRoute ( '_c' ) );
		$dataURL = \URLHelper::addParam ( $dataURL, '__s=json&_treeid=' . $id );
		$this->_url = $dataURL;
		$this->_baseLocale = $baseLocale;
		$this->_defaultRoot = $root;
		$this->setConfig ( 'autoEscape', false );
	}
	public function setAsyncMode($value) {
		$this->_asyncMode = $value;
	}
	public function setModel($model, $columnMapping = null) {
		$this->_columnMapping = $columnMapping ? $columnMapping : $this->_defaultMapping;
		$this->_model = $model;
	}
	public function setRootNode($value) {
		$this->setConfig ( 'root', $value );
	}
	private function colMapping($id) {
		return isset ( $this->_columnMapping [$id] ) ? $this->_columnMapping [$id] : $this->_defaultMapping [$id];
	}
	public function setTextParser($value) {
		$this->_textParser = $value;
	}
	protected function parseText($s, $o) {
		if ($this->_textParser) {
			return call_user_func ( $this->_textParser, $s, $o, $this );
		}
		return \Strings::replace ( $s, $o, $s, false, 0, '#', '#' );
	}
	public function setNodeText($value) {
		$this->_nodeText = $value;
	}
	protected function parseModelRows($rows, $loadChild = false) {
		$retval = array ();
		foreach ( $rows as $row ) {
			// ppd($row);
			$child = array ();
			if ($loadChild) {
				$child = $this->loadAll ( $row->id, $loadChild );
			}
			$r = new \stdClass ();
			$r->id = $row->id;
			$row->text = $this->_baseLocale ? __ ( $this->_baseLocale . '.' . $row->text, $row->text ) : $row->text;
			$r->label = $this->parseText ( $this->_nodeText ? $this->_nodeText : $row->text, $row );
			if (! $r->label)
				$r->label = '[NO TITLE]';
			if ($loadChild) {
				$r->children = $child;
			} else {
				$r->load_on_demand = $row->childs > 0;
			}
			$retval [] = $r;
		}
		return $retval;
	}
	private function loadAll($parent = 0, $loadChild = false) {
		$m = $this->_model;
		$m->reset ( 'tree', $this->getId () );
		$m->select ( $this->colMapping ( 'id' ), 'id' );
		$m->select ( $this->colMapping ( 'parent' ), 'parent' );
		$m->select ( $this->colMapping ( 'text' ), 'text' );
		$m->where ( $this->colMapping ( 'parent' ) . '=' . $m->quote ( $parent ) );
		$m->select ( '(select count(' . $this->colMapping ( 'id' ) . ') from ' . $m->getFirstTableName ()
        . ' c where c.' . $this->colMapping ( 'parent' ) . '=' . $m->getAlias () . '.id)', 'childs', true );
		// $m->groupBy($this->colMapping('parent'));
		return $this->parseModelRows ( $m->loadObjects (), $loadChild );
	}
	public function renderData() {
		if (! $this->_model) {
			throw new SystemException ( 'Invalid Model' );
		}
		$rparent = Request::get ( 'node', $this->_defaultRoot );
		if ($rparent === 'source') {
			$rparent = 0;
		}
		return $this->loadAll ( $this->getConfig ( 'root', $rparent ), false );
	}
	/**
	 *
	 * @param boolean $return
     * @return array|null|string|void
     */
	public function Render($return = false) {
		if (Request::isDataRequest ()) {
			return $this->renderData ();
		}
		CGAFJS::loadPlugin ( 'jqTree/tree.jquery', true );
		if ($this->_asyncMode) {
			// CGAFJS::loadPlugin ( 'jquery-treeview/jquery.treeview.async', true );
			// $this->setConfig ( 'url', $this->_url );
		}
		$retval = null;
		CGAFJS::addJQAsset ( 'plugins/jqTree/jqtree.css' );
		$id = $this->getId ();
		$parent = $this->getConfig ( 'renderTo', 'body' );
		$this->removeConfig ( 'renderTo' );
		$configs = JSON::encodeConfig ( $this->_configs );

		$js = '';
		if (! $return) {
			$c = urlencode ( '<div id="' . $id . '" data-url="' . $this->_url . '"></div>' );
			$js = <<<EOT
		if ($('#$id').length ===0) {
			$(decodeURIComponent('$c').replace('+',' ')).appendTo('$parent');
		}
EOT;
		} else {
			$retval = '<div id="' . $id . '" data-url="' . $this->_url . '"></div>';
		}
		$js .= '$(\'#' . $this->getId () . '\').tree(' . $configs . ')';
		$this->getAppOwner ()->addClientScript ( $js );
		if (! $return) {
			Response::write ( $retval );
		}
		return $retval;
	}
}

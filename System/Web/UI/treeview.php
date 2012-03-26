<?php
namespace System\Web\UI;
use \System\MVC\Models\TreeModel;
use \System\Web\UI\Controls\WebControl;

class TreeView extends WebControl {
	/**
	 * @var TreeModel
	 */
	private $_model;
	private $_nodeText;
	private $_parent;
	private $_baseLocale;
	private $_columnMapping = array();
	private $_selectedNode=null;
	private $_defaultMapping
	= array(
			'text'   => 'text',
			'id'     => 'id',
			'parent' => 'parent'
	);

	function __construct($id, $url = null, $baseLocale = null, $parent = 0) {
		parent::__construct('ul', false, array('class'=> 'treeview nav nav-list'));
		$this->_parent = 0;
		$this->_baseLocale = $baseLocale;
	}

	function setModel(TreeModel $m, $columnMapping = null) {
		$this->_columnMapping = $columnMapping ? $columnMapping : $this->_defaultMapping;
		$this->_model = $m;
	}

	function setNodeText($text) {
		$this->_nodeText = $text;
	}

	private function colMapping($id) {
		return isset($this->_columnMapping[$id]) ? $this->_columnMapping[$id] : $this->_defaultMapping[$id];
	}
	function setSelectedNodeId($id) {
		$this->_selectedNode = $id;
	}
	private function _renderNodes($items) {
		$pnode =new WebControl('li',false,array('class'=>'node'));
		foreach ($items as $row) {
			$ctext = $this->colMapping('text');
			$id =$this->colMapping('id');
			$s = new \stdClass();
			$s->id=$row->$id;
			$s->t = '';
			$s->text = $this->_baseLocale ? __($this->_baseLocale . '.' . $row->$ctext, $row->$ctext) : $row->$ctext;
			$r = $this->_nodeText ? $this->_nodeText :$s->text;
			$s->text = \Strings::replace($r, $s, $r, false, 0, '#', '#');
			$class='';
			if ($row->$id === $this->_selectedNode) {
				$class='active';
			}
			$pnode->addChild('<div class="'.$class.'">'
					.'<i class="'.($row->childs ? 'node-haschild' :'').'"></i>'
					.$s->text.'</div>');
			if ($row->childs) {
				$node = new WebControl('ul',false,array('class'=>'treeview nav nav-list'));
				$node->addChild($this->_renderNodes($row->childs));
				$pnode->addChild($node);
			}
		}
		return $pnode;
	}

	function prepareRender() {
		if (!$this->_renderPrepared) {
			parent::prepareRender();
			if ($this->_model) {
				$rows = $this->_model->loadParents($this->_parent);
				$this->addChild($this->_renderNodes($rows));
			}
		}
	}
}

?>
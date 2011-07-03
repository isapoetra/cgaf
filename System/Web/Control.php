<?php
if (! defined ( "CGAF" ))
	die ( "Restricted Access" );

class WebControl extends Control implements IRenderable {
	protected $_tag = "";
	protected $_attr = array ();
	private $_autoCloseTag = true;
	private $_title;
	private $_text;
	protected $_renderPrepared = false;
	function __construct($tag, $autoCloseTag = false, $attr = array()) {
		parent::__construct ();
		$attr = $attr ? $attr : array ();
		$this->_tag = $tag;
		if (! isset ( $attr ['id'] )) {
			$attr ['id'] = Utils::generateId ( 'c' );
		}
		$this->setAttr ( $attr, null );
		$this->_autoCloseTag = $autoCloseTag;

	}

	function addStyle($name, $value = null) {
		if (! isset ( $this->_attr ['style'] )) {
			$this->_attr ['style'] = array ();
		}
		$this->_attr ['style'] = HTMLUtils::mergeStyle ( $this->_attr ['style'], is_array ( $name ) ? $name : array (
				$name => $value
		) );
		return $this;
	}
	function add($c) {
		return parent::addChild ( $c );
	}
	function getText() {
		return $this->_text;
	}
	function setText($text) {
		$this->_text = $text;
	}

	function getId() {
		if (! $this->getProperty ( "id" )) {
			$this->setProperty ( "id", Utils::generateId ( 'c' ) );
		}
		return $this->getProperty ( "id" );
	}
	function setId($value) {
		$this->setProperty ( "id", $value );
	}
	function setAutoCloseTag($value) {
		$this->_autoCloseTag = $value;
	}
	function setTitle($value) {
		$this->_title = $value;
	}
	function getTitle() {
		return $this->_title;
	}
	protected function getAttr($name) {
		return $this->getProperty ( $name );
	}
	protected function hasAttr($name) {
		return isset ( $this->_attr [$name] );
	}
	function setConfig($attName, $Value = null) {
		return $this->setProperty ( $attName, $Value );
	}
	function setAttr($attName, $Value = null) {
		return $this->setProperty ( $attName, $Value );
	}
	function setTag($tag) {
		$this->_tag = $tag;
		return $this;
	}
	function renderAttributes($attr = null) {
		$attr = $attr ? $attr : $this->getProperties ();
		$retval = "";
		foreach ( $attr as $k => $v ) {
			if (is_string($v)) {
				$v = trim ( $v );
			}else{
				$v = Utils::arrayImplode($v,':',';');
			}
			
			if ($v !== null) {
				$retval .= " $k=\"$v\"";
			}
		}
		return $retval;
	}
	protected function RenderBeginTag() {

		$retval = "<$this->_tag" . $this->renderAttributes () . ($this->_autoCloseTag && (count ( $this->getChilds () ) == 0) && ! $this->getText () ? "/>" : ">");
		return $retval;
	}
	protected function renderEndTag() {
		return (! $this->_autoCloseTag || count ( $this->getChilds () ) > 0 || $this->getText () ? "</{$this->_tag}>" : "");
	}
	protected function renderItems() {
		$retval = "";
		foreach ( $this->getChilds () as $item ) {
			if (is_object ( $item ) && $item instanceof IRenderable) {
				$retval .= $item->render ( true );
			} elseif (is_string ( $item )) {
				$retval .= $item;
			} elseif (CGAF_DEBUG) {
				$retval .= pp ( $item, true );
			}
		}
		return $retval;
	}
	protected function renderBeginLabel() {
		return ($this->_title ? "<label>" . $this->_title : "");
	}
	protected function renderEndLabel() {
		return ($this->_title ? "</label>" : "");
	}
	/**
	 * @deprecated
	 */
	protected  function prepareRender() {
		$this->_renderPrepared = true;
		//$this->prepareRender ();

	}
	function Render($return = false) {
		if (!$this->_renderPrepared) {
			$this->prepareRender();
		}
		if (Request::isDataRequest ()) {
			return $this->renderItems ();
		}
		$retval = $this->renderBeginLabel ()
		. $this->RenderBeginTag ()
		. $this->_text . $this->renderItems ()
		. $this->renderEndTag ()
		. $this->renderEndLabel ();
		if (! $return) {
			Response::write ( $retval );
		}
		
		return $retval;
	}
}
class HTMLControl extends WebControl {
	
}
class HTMLInput extends WebControl {
	function __construct($type) {
		parent::__construct('input');
		$this->setAttr('type',$type);
	}
}
?>

<?php
namespace System\DB;
use \Strings;
class  DBReflectionClass extends \ReflectionClass {
	private $_fields =array();
	private $_pk=array();
	function __construct($argument) {
		parent::__construct($argument);
		$props = $this->getProperties ();
		foreach ( $props as $prop ) {
			$name = $prop->getName ();
			if (Strings::BeginWith ( $name, "_" )) {
				continue;
			}
			$doc =new DBFieldDefs(\PHPDocHelper::parse ( $prop->getDocComment () ));
			$doc->FieldName = $name;

			$this->_fields[$name] = $doc;

			//$this->select ( $this->getConnection ()->parseFieldCreate ( $name, $type, $fLength, $defaultValue ) );
		}
	}
	function getPrimaryKey() {
		if ($this->_pk) {
			return $this->_pk;
		}
		$this->_pk = array();
		foreach ($this->_fields as $field) {
			if ($field->fieldisprimarykey) {
				$this->_pk = $field->fieldname;
			}
		}
		return $this->_pk;
	}
	function getFields() {
		return $this->_fields;
	}
}
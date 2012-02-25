<?php
namespace System\MVC;
use System\DB\Table;
class Model extends Table {
	function __construct($connection, $tableName, $pk = "id", $includeAppId = false,$autoCreate = false) {
		parent::__construct ( $connection, $tableName, $pk, $includeAppId ,$autoCreate);
	}
	function getModel($model) {
		return $this->getAppOwner ()->getModel ( $model );
	}
	function reset($mode = null, $id = null) {
		return $this->clear ();
	}
	function resetgrid($id = null) {
		return $this->reset ();
	}
	protected function _createTable() {
		if (parent::_createTable ()) {
			$tname = $this->getConnection ()->getInstallFile ( $this->_tableName );
			if ($tname) {
				$this->clear ();
				if ($this->loadSQLFile ( $tname )) {
					$r = $this->exec ();
					if ($r && $error= $r->getError()) {
						$msgs = array();
						foreach($error as $e) {
							$msgs[] =  $e->getMessage();
						}
						throw new \Exception('Error While Loading file '.$tname.implode("\n",$msgs));
					}
				}
			}
			$this->clear ();
			return true;
		}
		return false;
	}
	function joinComment($type, $relId) {
		$this->join ( '(select comment_object,count(*) total_comment from #__comment where  comment_type=\'' . $type . '\' and comment_status=0 group by comment_object)', 'tcomment', 'tcomment.comment_object=' . $relId, 'left', true );
		$this->join ( '(select comment_object,count(*) total_comment from #__comment where  comment_type=\'' . $type . '\' group by comment_object)', 'ucomment', 'ucomment.comment_object=' . $relId, 'left', true );
		return $this;
	}
	function newData() {
		return $this->clear ( 'all' );
	}
}
?>
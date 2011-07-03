<?php

defined ( "CGAF" ) or die ( "Restricted Access" );
using ( "System.DB.Table" );
class MVCModel extends DBTable {

	function getModel($model) {
		return $this->getAppOwner ()->getModel ( $model );
	}
	function reset() {
		return $this->clear ();
	}
	function resetgrid($id = null) {
		return $this->reset ();
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
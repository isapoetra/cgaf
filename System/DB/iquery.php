<?php
namespace System\DB;
interface IQuery {

	/**
	 *
	 * @param $field
	 * @return IQuery
	 */
	function select($field);
	/**
	 *
	 * @param $sql
	 * @return IQuery
	 */
	function addSQL($sql);

	/**
	 * @param $field
	 * @param $value
	 * @return IQuery
	 */
	function addInsert($field,$value=null,$func=false);
	/**
	 *
	 * @param $order
	 * @return IQuery
	 */
	function addOrder($order) ;
	/**
	 * Enter description here...
	 *
	 * @param string $where
	 * @param string $next
	 * @return IQuery
	 */
	function Where($where, $next = 'AND');
	/**
	 *
	 * @return Array<Object>
	 */
	function loadObjects($class=null,$page=0,$rowPerPage =-1);
	/**
	 *
	 * @param $f
	 * @return IQuery
	 */
	function loadSQLFile($f);
	/**
	 *
	 * @return String
	 */
	function getDriverString();

	/**
	 *
	 * @param $table
	 * @param $alias
	 * @param $expr
	 * @param $m
	 * @param $vw
	 * @return IQuery
	 */
	function join($table, $alias, $expr, $m = "inner",$vw =false);
}
?>
<?php
namespace System\DB;
interface IDBConnection extends \IConnection
{
    /**
     *
     * @param boolean
     * @return void
     */
    function setThrowOnError($value);

    function fetchAssoc();

    /**
     * @param $sql
     * @return DBResultList | null
     */
    function exec($sql);

    /**
     * @param $sql
     * @return DBResultList | null
     */
    function query($sql);

    function fieldTypeToPHP($type);

    /**
     * @return DBResultList
     */
    function getTableList();

    /**
     * @return int
     */
    function getLastInsertId();

    /**
     * @param $objectId
     * @param string $objectType
     * @param bool $throw
     * @return mixed
     */
    function getObjectInfo($objectId, $objectType = "table", $throw = true);

    /**
     * @param string $str
     * @param bool $prep
     * @return string
     */
    function quote($str, $prep = true);

    /**
     * @param string $table
     * @param bool $includedbname
     * @return string
     */
    function quoteTable($table, $includedbname = false);
}

?>
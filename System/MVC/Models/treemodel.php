<?php
namespace System\MVC\Models;

use System\MVC\Model;

class TreeModel extends Model
{
    private $_parentField;

    function __construct($connection, $tableName, $pk, $parentField, $includeAppId = false, $autoCreate = null)
    {
        parent::__construct($connection, $tableName, $pk, $includeAppId, $autoCreate);
        $this->_parentField = $parentField;
    }

    function loadParents($parent, $page = -1, $rowPerPage = -1, $loadChild = true)
    {
        if ($parent === null) {
            return array();
        }
        $this->reset('tree-parent');
        $this->Where($this->_parentField . '=' . $this->quote($parent));
        $rows = $this->loadObjects();
        $pk = implode('', $this->getPK());
        foreach ($rows as $row) {
            if (!isset($row->{$pk})) {
                ppd($row);
            }
            if ($loadChild) {
                $childs = $this->loadParents($row->{$pk});
                $row->childs = $childs;
            } else {
                $row->childs = array();
            }
        }
        return $rows;
    }

    function reset($mode = null, $id = null)
    {
        parent::reset($mode, $id);
        switch ($mode) {
            case 'tree-parent';
                $pk = $this->getPK();
                $r = array();
                foreach ($pk as $p) {
                    $r[] = $this->quoteField($p);
                }
                $this->select(implode('+', $r) . ' as __pk');
        }
        return $this;
    }

    function LoadAll($page = -1, $rowPerPage = -1)
    {
        return $this->loadParents(0, $page, $rowPerPage);
    }
}

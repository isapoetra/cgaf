<?php
namespace System\Collections;
use System\Exceptions\SystemException;

use System\Collections\Items\SearchItem;

class SearchItemCollection extends Collection
{
    private $_resultCount = 0;

    public function add($item, $multi = false)
    {
        if ($item instanceof SearchItem) {
            return parent::add($item, false);
        }
        return -1;
    }

    function setResultCount($value)
    {
        $this->_resultCount = $value;
    }

    function getResultCount()
    {
        return $this->_resultCount;
    }

    function renderAs($f)
    {
        $retval = '';
        switch ($f) {
            case 'html':
                foreach ($this as $v) {
                    /** @noinspection PhpUndefinedMethodInspection */
                    $retval .= $v->renderAs($f);
                }
                break;
            default:
                throw new SystemException('unhandle output format ' . $f);
                break;
        }
        return $retval;

    }
}